<?php

namespace Tests\Feature;

use App\Models\Attachment;
use App\Models\Employee;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class EmployeeRequestsTest extends TestCase
{
    use RefreshDatabase;

    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(Attachment::DISK);
        $this->employee = Employee::factory()->create();
        Sanctum::actingAs($this->employee);
    }

    public function test_sick_leave_requires_a_medical_certificate(): void
    {
        $this->postJson('/api/me/leave-requests', $this->leavePayload('Sick'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('attachment_medical_certificate');
    }

    public function test_sick_leave_with_certificate_is_stored_with_attachment(): void
    {
        $payload = $this->leavePayload('Sick') + [
            'attachment_medical_certificate' => UploadedFile::fake()->create('certificate.pdf', 200, 'application/pdf'),
        ];

        $response = $this->post('/api/me/leave-requests', $payload, ['Accept' => 'application/json']);

        $response->assertCreated()
            ->assertJsonPath('data.status', 'Pending')
            ->assertJsonPath('data.attachments.0.document_key', 'medical_certificate');

        $attachment = Attachment::firstOrFail();
        Storage::disk(Attachment::DISK)->assertExists($attachment->stored_path);
    }

    public function test_admin_can_see_and_download_the_uploaded_leave_proof(): void
    {
        $payload = $this->leavePayload('Sick') + [
            'attachment_medical_certificate' => UploadedFile::fake()->image('certificate.png'),
        ];
        $this->post('/api/me/leave-requests', $payload, ['Accept' => 'application/json'])->assertCreated();

        Sanctum::actingAs(Employee::factory()->admin()->create());

        $response = $this->getJson('/api/admin/requests?status=Pending')
            ->assertOk()
            ->assertJsonPath('data.0.attachments.0.document_label', 'Medical Certificate')
            ->assertJsonPath('data.0.attachments.0.original_name', 'certificate.png');

        $this->get('/api/attachments/' . $response->json('data.0.attachments.0.id'))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/png');
    }

    public function test_vacation_leave_does_not_require_documents(): void
    {
        $this->postJson('/api/me/leave-requests', $this->leavePayload('Vacation'))->assertCreated();
    }

    public function test_leave_end_date_must_not_be_before_start_date(): void
    {
        $payload = ['end_date' => now()->addDay()->toDateString()] + $this->leavePayload('Vacation');

        $this->postJson('/api/me/leave-requests', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('end_date');
    }

    public function test_resignation_requires_a_letter(): void
    {
        $this->postJson('/api/me/resignation-requests', [
            'resignation_date' => now()->addMonth()->toDateString(),
            'reason' => 'Relocating.',
        ])->assertUnprocessable()->assertJsonValidationErrors('attachment_resignation_letter');
    }

    public function test_dashboard_reports_pending_requests(): void
    {
        $this->postJson('/api/me/subject-requests', ['subject_code' => 'IT101', 'subject_name' => 'Intro', 'units' => 3])
            ->assertCreated();
        $this->postJson('/api/me/task-requests', [
            'task_title' => 'Write syllabus',
            'task_description' => 'For next term',
            'due_date' => now()->addWeek()->toDateString(),
            'task_status' => 'Pending',
        ])->assertCreated();

        $this->getJson('/api/me/dashboard')
            ->assertOk()
            ->assertJsonPath('pending.subject', 1)
            ->assertJsonPath('pending.task', 1)
            ->assertJsonPath('pending.total', 2);
    }

    public function test_employee_cannot_open_another_employees_attachment(): void
    {
        $other = Employee::factory()->create();
        $leave = $other->leaveRequests()->create($this->leavePayload('Vacation'));
        $attachment = $leave->attachments()->create([
            'employee_id' => $other->id,
            'document_key' => 'supporting_document',
            'document_label' => 'Supporting Document',
            'original_name' => 'a.pdf',
            'stored_path' => 'attachments/x/a.pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 10,
        ]);

        $this->getJson("/api/attachments/{$attachment->id}")->assertForbidden();
    }

    private function leavePayload(string $type): array
    {
        return [
            'leave_type' => $type,
            'start_date' => now()->addDays(3)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'reason' => 'Personal reasons.',
        ];
    }
}
