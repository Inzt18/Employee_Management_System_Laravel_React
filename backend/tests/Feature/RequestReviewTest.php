<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RequestReviewTest extends TestCase
{
    use RefreshDatabase;

    private Employee $admin;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Employee::factory()->admin()->create();
        $this->employee = Employee::factory()->create();
        Sanctum::actingAs($this->admin);
    }

    public function test_inbox_lists_pending_requests_of_every_type(): void
    {
        $this->createRequests();

        $this->getJson('/api/admin/requests?status=Pending')
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $this->getJson('/api/admin/dashboard')->assertOk()->assertJsonPath('pending.total', 4);
    }

    public function test_approving_a_subject_request_assigns_the_subject_and_reuses_existing_code(): void
    {
        Subject::create(['subject_code' => 'IT300', 'subject_name' => 'Existing', 'units' => 3]);
        $request = $this->employee->subjectRequests()->create(['subject_code' => 'IT300', 'subject_name' => 'Dup', 'units' => 3]);

        $this->patchJson("/api/admin/requests/subject/{$request->id}", ['status' => 'Approved'])
            ->assertOk()
            ->assertJsonPath('data.status', 'Approved')
            ->assertJsonPath('data.reviewer', $this->admin->full_name);

        $this->assertDatabaseCount('subjects', 1);
        $this->assertDatabaseHas('employee_subjects', ['employee_id' => $this->employee->id]);
    }

    public function test_approving_a_task_request_creates_the_task(): void
    {
        $request = $this->employee->taskRequests()->create([
            'task_title' => 'Lesson plan',
            'task_description' => 'Week 1',
            'due_date' => now()->addWeek()->toDateString(),
            'task_status' => 'Ongoing',
        ]);

        $this->patchJson("/api/admin/requests/task/{$request->id}", ['status' => 'Approved'])->assertOk();

        $this->assertDatabaseHas('tasks', [
            'employee_id' => $this->employee->id,
            'task_title' => 'Lesson plan',
            'status' => 'Ongoing',
        ]);
    }

    public function test_rejecting_requires_a_comment_and_cannot_be_reviewed_twice(): void
    {
        $request = $this->employee->leaveRequests()->create([
            'leave_type' => 'Vacation',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Rest',
        ]);

        $this->patchJson("/api/admin/requests/leave/{$request->id}", ['status' => 'Rejected'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('admin_comment');

        $this->patchJson("/api/admin/requests/leave/{$request->id}", ['status' => 'Rejected', 'admin_comment' => 'Peak season'])
            ->assertOk()
            ->assertJsonPath('data.admin_comment', 'Peak season');

        $this->patchJson("/api/admin/requests/leave/{$request->id}", ['status' => 'Approved'])
            ->assertUnprocessable();
    }

    public function test_unknown_request_type_returns_not_found(): void
    {
        $this->patchJson('/api/admin/requests/payroll/1', ['status' => 'Approved'])->assertNotFound();
    }

    private function createRequests(): void
    {
        $this->employee->leaveRequests()->create([
            'leave_type' => 'Vacation',
            'start_date' => now()->addDay()->toDateString(),
            'end_date' => now()->addDays(2)->toDateString(),
            'reason' => 'Rest',
        ]);
        $this->employee->resignationRequests()->create([
            'resignation_date' => now()->addMonth()->toDateString(),
            'reason' => 'Moving',
        ]);
        $this->employee->subjectRequests()->create(['subject_code' => 'IT1', 'subject_name' => 'A', 'units' => 3]);
        $this->employee->taskRequests()->create([
            'task_title' => 'T',
            'task_description' => 'D',
            'due_date' => now()->addWeek()->toDateString(),
            'task_status' => 'Pending',
        ]);
    }
}
