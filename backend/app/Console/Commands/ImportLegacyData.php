<?php

namespace App\Console\Commands;

use App\Enums\EmploymentStatus;
use App\Enums\LeaveType;
use App\Enums\RequestStatus;
use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Models\Attachment;
use App\Models\Employee;
use Illuminate\Console\Command;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Copies the data of the original native PHP project into the Laravel schema.
 * The legacy database is only read, never modified.
 */
class ImportLegacyData extends Command
{
    protected $signature = 'legacy:import
        {--fresh : Drop and re-create all tables before importing}
        {--uploads= : Folder of the legacy uploads/requests directory}';

    protected $description = 'Import employees, subjects, schedules, tasks and requests from the native PHP database';

    private ConnectionInterface $legacy;

    /** @var array<int, int> legacy subject_id => new subject id */
    private array $subjectIds = [];

    /** @var array<int, int> legacy employee_subject_id => new employee_subjects id */
    private array $assignmentIds = [];

    /** @var array<int, true> employee ids that exist in the new database */
    private array $employeeIds = [];

    public function handle(): int
    {
        $this->legacy = DB::connection('legacy');

        if ($this->option('fresh')) {
            $this->call('migrate:fresh', ['--force' => true]);
        } elseif (Employee::query()->exists()) {
            $this->error('The Laravel database already has employees. Re-run with --fresh to start from an empty database.');

            return self::FAILURE;
        }

        DB::transaction(function () {
            $this->importEmployees();
            $this->importSubjects();
            $this->importAssignmentsAndSchedules();
            $this->importTasks();
            $this->importLeaveRequests();
            $this->importResignationRequests();
            $this->importSubjectRequests();
            $this->importTaskRequests();
            $this->importAttachments();
        });

        $this->info('Legacy data imported successfully.');

        return self::SUCCESS;
    }

    private function importEmployees(): void
    {
        $rows = $this->legacy->table('employees')->orderBy('employee_id')->get();

        foreach ($rows as $row) {
            DB::table('employees')->insert([
                'id' => $row->employee_id,
                'first_name' => $row->first_name,
                'middle_name' => $row->middle_name ?: null,
                'last_name' => $row->last_name,
                'birth_date' => $row->birth_date,
                'gender' => $this->normalizedGender($row),
                'email' => $row->email,
                'department' => $row->department,
                'address' => $row->address,
                'username' => $row->username,
                'password' => $this->hashedPassword((string) $row->password),
                'role' => Role::tryFrom((string) $row->role)?->value ?? Role::Employee->value,
                'employment_status' => EmploymentStatus::tryFrom((string) $row->employment_status)?->value ?? EmploymentStatus::Active->value,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => now(),
            ]);

            $this->employeeIds[$row->employee_id] = true;
        }

        $this->line("Employees: {$rows->count()}");
    }

    /**
     * Only Male and Female are supported; unrecognised values are flagged so they can be corrected afterwards.
     */
    private function normalizedGender(object $row): string
    {
        $gender = match (strtolower(trim((string) $row->gender))) {
            'male', 'm' => 'Male',
            'female', 'f' => 'Female',
            default => null,
        };

        if ($gender === null) {
            $this->warn("Employee #{$row->employee_id} has gender \"{$row->gender}\"; set to Male. Please update it in the admin panel.");
        }

        return $gender ?? 'Male';
    }

    /**
     * The old app accepted plain-text passwords; anything not already hashed is hashed now.
     */
    private function hashedPassword(string $stored): string
    {
        return Hash::isHashed($stored) ? $stored : Hash::make($stored);
    }

    /**
     * Subject codes are unique in the new schema, so duplicate codes are merged.
     */
    private function importSubjects(): void
    {
        $byCode = [];

        foreach ($this->legacy->table('subjects')->orderBy('subject_id')->get() as $row) {
            $code = trim($row->subject_code);

            $byCode[$code] ??= DB::table('subjects')->insertGetId([
                'subject_code' => $code,
                'subject_name' => $row->subject_name,
                'units' => max(1, (int) $row->units),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->subjectIds[$row->subject_id] = $byCode[$code];
        }

        $this->line('Subjects: ' . count($byCode));
    }

    private function importAssignmentsAndSchedules(): void
    {
        $existing = [];

        foreach ($this->legacy->table('employee_subjects')->orderBy('employee_subject_id')->get() as $row) {
            $subjectId = $this->subjectIds[$row->subject_id] ?? null;
            if ($subjectId === null || ! isset($this->employeeIds[$row->employee_id])) {
                continue;
            }

            $key = "{$row->employee_id}:{$subjectId}";
            $existing[$key] ??= DB::table('employee_subjects')->insertGetId([
                'employee_id' => $row->employee_id,
                'subject_id' => $subjectId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->assignmentIds[$row->employee_subject_id] = $existing[$key];
        }

        $scheduled = [];
        foreach ($this->legacy->table('schedules')->orderBy('schedule_id')->get() as $row) {
            $assignmentId = $this->assignmentIds[$row->employee_subject_id] ?? null;
            if ($assignmentId === null || isset($scheduled[$assignmentId])) {
                continue;
            }

            DB::table('schedules')->insert([
                'employee_subject_id' => $assignmentId,
                'day' => $row->day,
                'start_time' => $row->start_time,
                'end_time' => $row->end_time,
                'room' => $row->room,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $scheduled[$assignmentId] = true;
        }

        $this->line('Subject assignments: ' . count($existing) . ', schedules: ' . count($scheduled));
    }

    private function importTasks(): void
    {
        $count = 0;

        foreach ($this->legacy->table('tasks')->orderBy('task_id')->get() as $row) {
            if (! isset($this->employeeIds[$row->employee_id])) {
                continue;
            }

            DB::table('tasks')->insert([
                'id' => $row->task_id,
                'employee_id' => $row->employee_id,
                'task_title' => $row->task_title,
                'task_description' => $row->task_description,
                'due_date' => $row->due_date,
                'status' => TaskStatus::tryFrom((string) $row->status)?->value ?? TaskStatus::Pending->value,
                'created_at' => $row->created_at ?? now(),
                'updated_at' => now(),
            ]);
            $count++;
        }

        $this->line("Tasks: {$count}");
    }

    private function importLeaveRequests(): void
    {
        $count = 0;

        foreach ($this->legacy->table('leave_requests')->orderBy('leave_id')->get() as $row) {
            if (! isset($this->employeeIds[$row->employee_id])) {
                continue;
            }

            DB::table('leave_requests')->insert([
                'id' => $row->leave_id,
                'employee_id' => $row->employee_id,
                'leave_type' => $this->normalizeLeaveType((string) $row->leave_type),
                'start_date' => $row->start_date,
                'end_date' => $row->end_date,
                'reason' => $row->reason,
                ...$this->reviewColumns($row->status, $row->admin_comment, $row->request_date),
            ]);
            $count++;
        }

        $this->line("Leave requests: {$count}");
    }

    private function normalizeLeaveType(string $value): string
    {
        foreach (LeaveType::cases() as $type) {
            if (Str::startsWith(Str::lower($value), Str::lower($type->value))) {
                return $type->value;
            }
        }

        return $value !== '' ? $value : LeaveType::Vacation->value;
    }

    private function importResignationRequests(): void
    {
        $count = 0;

        foreach ($this->legacy->table('resignation_requests')->orderBy('resignation_id')->get() as $row) {
            if (! isset($this->employeeIds[$row->employee_id])) {
                continue;
            }

            DB::table('resignation_requests')->insert([
                'id' => $row->resignation_id,
                'employee_id' => $row->employee_id,
                'resignation_date' => $row->resignation_date,
                'reason' => (string) $row->reason,
                ...$this->reviewColumns($row->status, $row->admin_comment, $row->request_date),
            ]);
            $count++;
        }

        $this->line("Resignation requests: {$count}");
    }

    private function importSubjectRequests(): void
    {
        $count = 0;

        foreach ($this->legacy->table('subject_requests')->orderBy('request_id')->get() as $row) {
            if (! isset($this->employeeIds[$row->employee_id])) {
                continue;
            }

            DB::table('subject_requests')->insert([
                'id' => $row->request_id,
                'employee_id' => $row->employee_id,
                'subject_code' => Str::limit(trim($row->subject_code), 20, ''),
                'subject_name' => Str::limit($row->subject_name, 100, ''),
                'units' => max(1, (int) $row->units),
                ...$this->reviewColumns($row->status, $row->admin_comment, $row->request_date),
            ]);
            $count++;
        }

        $this->line("Subject requests: {$count}");
    }

    private function importTaskRequests(): void
    {
        $count = 0;

        foreach ($this->legacy->table('task_requests')->orderBy('request_id')->get() as $row) {
            if (! isset($this->employeeIds[$row->employee_id])) {
                continue;
            }

            DB::table('task_requests')->insert([
                'id' => $row->request_id,
                'employee_id' => $row->employee_id,
                'task_title' => Str::limit($row->task_title, 150, ''),
                'task_description' => $row->task_description,
                'due_date' => $row->due_date,
                'task_status' => TaskStatus::tryFrom((string) $row->status)?->value ?? TaskStatus::Pending->value,
                ...$this->reviewColumns($row->request_status, $row->admin_comment, $row->request_date),
            ]);
            $count++;
        }

        $this->line("Task requests: {$count}");
    }

    private function reviewColumns(?string $status, ?string $comment, ?string $requestedAt): array
    {
        $status = RequestStatus::tryFrom((string) $status) ?? RequestStatus::Pending;

        return [
            'status' => $status->value,
            'admin_comment' => $comment,
            'reviewed_at' => $status === RequestStatus::Pending ? null : now(),
            'created_at' => $requestedAt ?? now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Copies uploaded documents from the old uploads/requests folder into Laravel storage.
     */
    private function importAttachments(): void
    {
        $uploadsPath = rtrim($this->option('uploads') ?: base_path('../../employee_management/uploads/requests'), '/\\');
        $tables = ['leave' => 'leave_requests', 'resignation' => 'resignation_requests'];
        $count = 0;

        foreach ($this->legacy->table('request_attachments')->orderBy('attachment_id')->get() as $row) {
            $source = $uploadsPath . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $row->stored_name);
            $table = $tables[$row->request_type] ?? null;

            if ($table === null || ! is_file($source) || ! DB::table($table)->where('id', $row->request_id)->exists()) {
                continue;
            }

            $path = "attachments/{$row->employee_id}/" . basename($row->stored_name);
            Storage::disk(Attachment::DISK)->put($path, file_get_contents($source));

            DB::table('attachments')->insert([
                'attachable_type' => $row->request_type,
                'attachable_id' => $row->request_id,
                'employee_id' => $row->employee_id,
                'document_key' => $row->document_key,
                'document_label' => Str::headline($row->document_key),
                'original_name' => $row->original_name,
                'stored_path' => $path,
                'mime_type' => $row->mime_type,
                'file_size' => $row->file_size,
                'created_at' => $row->uploaded_at,
                'updated_at' => now(),
            ]);
            $count++;
        }

        $this->line("Attachments: {$count}");
    }
}
