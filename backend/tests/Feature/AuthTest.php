<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_employee_can_register_and_receives_a_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'birth_date' => '1999-02-03',
            'gender' => 'Female',
            'email' => 'ana@example.com',
            'department' => 'Marketing and Sales',
            'address' => 'Cebu',
            'username' => 'ana',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'role' => 'Admin',
        ]);

        $response->assertCreated()
            ->assertJsonPath('employee.role', 'Employee')
            ->assertJsonPath('employee.department', 'Marketing and Sales')
            ->assertJsonStructure(['token', 'employee' => ['id', 'full_name']]);
    }

    public function test_register_only_accepts_male_or_female(): void
    {
        $this->postJson('/api/auth/register', ['gender' => 'Other'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('gender');
    }

    public function test_register_rejects_duplicate_username(): void
    {
        Employee::factory()->create(['username' => 'taken']);

        $this->postJson('/api/auth/register', ['username' => 'taken'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['username' => 'That username is already taken.']);
    }

    public function test_login_with_valid_credentials(): void
    {
        Employee::factory()->create(['username' => 'juan', 'password' => 'password123']);

        $this->postJson('/api/auth/login', ['username' => 'juan', 'password' => 'password123'])
            ->assertOk()
            ->assertJsonStructure(['token', 'employee']);
    }

    public function test_login_with_wrong_password_fails(): void
    {
        Employee::factory()->create(['username' => 'juan']);

        $this->postJson('/api/auth/login', ['username' => 'juan', 'password' => 'wrong-password'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('username');
    }

    public function test_inactive_employee_cannot_log_in(): void
    {
        Employee::factory()->inactive()->create(['username' => 'juan', 'password' => 'password123']);

        $this->postJson('/api/auth/login', ['username' => 'juan', 'password' => 'password123'])
            ->assertForbidden();
    }

    public function test_deactivated_employee_is_signed_out(): void
    {
        Sanctum::actingAs(Employee::factory()->inactive()->create());

        $this->getJson('/api/auth/me')->assertUnauthorized();
    }

    public function test_guests_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/auth/me')->assertUnauthorized();
        $this->getJson('/api/admin/employees')->assertUnauthorized();
    }

    public function test_employees_cannot_access_admin_routes(): void
    {
        Sanctum::actingAs(Employee::factory()->create());

        $this->getJson('/api/admin/employees')->assertForbidden();
    }

    public function test_logout_revokes_the_token(): void
    {
        Employee::factory()->create(['username' => 'juan', 'password' => 'password123']);
        $token = $this->postJson('/api/auth/login', ['username' => 'juan', 'password' => 'password123'])->json('token');

        $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
