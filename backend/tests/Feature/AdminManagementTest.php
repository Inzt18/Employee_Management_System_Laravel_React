<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use RefreshDatabase;

    private Employee $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Employee::factory()->admin()->create();
        Sanctum::actingAs($this->admin);
    }

    public function test_admin_can_create_search_update_and_delete_employees(): void
    {
        $id = $this->postJson('/api/admin/employees', $this->employeePayload())
            ->assertCreated()
            ->json('data.id');

        $this->getJson('/api/admin/employees?search=Mercado')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->putJson("/api/admin/employees/{$id}", ['password' => ''] + $this->employeePayload(['department' => 'Finance']))
            ->assertOk()
            ->assertJsonPath('data.department', 'Finance');

        $this->deleteJson("/api/admin/employees/{$id}")->assertOk();
        $this->assertDatabaseMissing('employees', ['id' => $id]);
    }

    public function test_admin_cannot_delete_or_deactivate_themselves(): void
    {
        $this->deleteJson("/api/admin/employees/{$this->admin->id}")->assertUnprocessable();

        $payload = $this->employeePayload([
            'username' => $this->admin->username,
            'email' => $this->admin->email,
            'role' => 'Admin',
            'employment_status' => 'Inactive',
        ]);
        $this->putJson("/api/admin/employees/{$this->admin->id}", $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('employment_status');
    }

    public function test_subject_codes_must_be_unique(): void
    {
        Subject::create(['subject_code' => 'IT101', 'subject_name' => 'Intro', 'units' => 3]);

        $this->postJson('/api/admin/subjects', ['subject_code' => 'IT101', 'subject_name' => 'Other', 'units' => 3])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject_code');
    }

    public function test_assign_new_subject_with_schedule_then_update_and_remove(): void
    {
        $employee = Employee::factory()->create();

        $assignmentId = $this->postJson("/api/admin/employees/{$employee->id}/subjects", [
            'new_subject_code' => 'IT200',
            'new_subject_name' => 'Web Development',
            'new_units' => 3,
            'day' => 'Monday',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'room' => 'Lab 1',
        ])->assertCreated()->json('data.id');

        $this->getJson("/api/admin/employees/{$employee->id}/subjects")
            ->assertOk()
            ->assertJsonPath('data.0.subject.subject_code', 'IT200')
            ->assertJsonPath('data.0.schedule.room', 'Lab 1');

        $this->putJson("/api/admin/assignments/{$assignmentId}", [
            'day' => 'Friday', 'start_time' => '13:00', 'end_time' => '12:00', 'room' => 'Lab 2',
        ])->assertUnprocessable()->assertJsonValidationErrors('end_time');

        $this->putJson("/api/admin/assignments/{$assignmentId}", [
            'day' => 'Friday', 'start_time' => '13:00', 'end_time' => '15:00', 'room' => 'Lab 2',
        ])->assertOk()->assertJsonPath('data.schedule.day', 'Friday');

        $this->getJson("/api/admin/employees/{$employee->id}/available-subjects")
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->deleteJson("/api/admin/assignments/{$assignmentId}")->assertOk();
        $this->assertDatabaseCount('schedules', 0);
    }

    public function test_admin_can_manage_employee_tasks(): void
    {
        $employee = Employee::factory()->create();

        $taskId = $this->postJson("/api/admin/employees/{$employee->id}/tasks", [
            'task_title' => 'Encode grades',
            'due_date' => now()->addWeek()->toDateString(),
            'status' => 'Pending',
        ])->assertCreated()->json('data.id');

        $this->putJson("/api/admin/tasks/{$taskId}", ['task_title' => 'Encode grades', 'status' => 'Completed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Completed');

        $this->getJson('/api/admin/task-templates')->assertOk()->assertJsonPath('data.0.task_title', 'Encode grades');

        $this->deleteJson("/api/admin/tasks/{$taskId}")->assertOk();
    }

    private function employeePayload(array $overrides = []): array
    {
        return $overrides + [
            'first_name' => 'Maria',
            'last_name' => 'Mercado',
            'birth_date' => '1995-07-07',
            'gender' => 'Female',
            'email' => 'maria@example.com',
            'department' => 'Registrar',
            'address' => 'Davao',
            'username' => 'maria',
            'password' => 'password123',
            'role' => 'Employee',
            'employment_status' => 'Active',
        ];
    }
}
