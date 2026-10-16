<?php

namespace Tests\Feature;

use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class SocialAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.frontend_url' => 'http://localhost:5173',
            'services.google' => ['client_id' => 'g-id', 'client_secret' => 'g-secret', 'redirect' => 'http://127.0.0.1:8000/api/auth/google/callback'],
            'services.facebook' => ['client_id' => 'fb-id', 'client_secret' => 'fb-secret', 'redirect' => 'http://127.0.0.1:8000/api/auth/facebook/callback'],
        ]);
    }

    private function fakeSocialUser(string $provider, string $id, ?string $email): void
    {
        $user = (new SocialiteUser)->map(['id' => $id, 'email' => $email, 'avatar' => "https://example.com/{$id}.jpg"]);

        $driver = Mockery::mock();
        $driver->shouldReceive('stateless')->andReturnSelf();
        $driver->shouldReceive('user')->andReturn($user);

        Socialite::shouldReceive('driver')->with($provider)->andReturn($driver);
    }

    public function test_providers_endpoint_lists_google_and_facebook(): void
    {
        $this->getJson('/api/auth/providers')
            ->assertOk()
            ->assertExactJson(['google' => true, 'facebook' => true]);

        config(['services.facebook.client_id' => null]);

        $this->getJson('/api/auth/providers')->assertExactJson(['google' => true, 'facebook' => false]);
    }

    public function test_unconfigured_or_unknown_providers_are_not_found(): void
    {
        config(['services.facebook.client_id' => null]);

        $this->get('/api/auth/facebook/redirect')->assertNotFound();
        $this->get('/api/auth/twitter/redirect')->assertNotFound();
    }

    public function test_facebook_login_links_the_matching_employee_and_returns_a_token(): void
    {
        $employee = Employee::factory()->create(['email' => 'ana@example.com']);
        $this->fakeSocialUser('facebook', 'fb-123', 'ana@example.com');

        $response = $this->get('/api/auth/facebook/callback');

        $response->assertRedirect();
        $this->assertStringStartsWith('http://localhost:5173/auth/callback#token=', $response->headers->get('Location'));
        $this->assertSame('fb-123', $employee->fresh()->facebook_id);
        $this->assertSame(1, $employee->tokens()->count());
    }

    public function test_google_login_still_works(): void
    {
        $employee = Employee::factory()->create(['email' => 'ben@example.com']);
        $this->fakeSocialUser('google', 'g-456', 'ben@example.com');

        $this->get('/api/auth/google/callback')->assertRedirect();

        $this->assertSame('g-456', $employee->fresh()->google_id);
        $this->assertNull($employee->fresh()->facebook_id);
    }

    public function test_facebook_login_without_a_matching_account_is_rejected(): void
    {
        $this->fakeSocialUser('facebook', 'fb-999', 'stranger@example.com');

        $location = $this->get('/api/auth/facebook/callback')->headers->get('Location');

        $this->assertStringContainsString('#error=', $location);
        $this->assertStringContainsString(urlencode('stranger@example.com'), $location);
    }

    public function test_facebook_account_without_email_is_rejected_with_a_clear_message(): void
    {
        Employee::factory()->create();
        $this->fakeSocialUser('facebook', 'fb-555', null);

        $location = $this->get('/api/auth/facebook/callback')->headers->get('Location');

        $this->assertStringContainsString('#error=', $location);
        $this->assertStringContainsString('email', urldecode($location));
    }

    public function test_inactive_employee_cannot_sign_in_with_facebook(): void
    {
        $employee = Employee::factory()->create(['email' => 'cara@example.com', 'employment_status' => 'Inactive']);
        $this->fakeSocialUser('facebook', 'fb-777', 'cara@example.com');

        $location = $this->get('/api/auth/facebook/callback')->headers->get('Location');

        $this->assertStringContainsString('#error=', $location);
        $this->assertSame(0, $employee->tokens()->count());
    }
}
