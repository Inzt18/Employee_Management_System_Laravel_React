<?php

namespace Database\Seeders;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Models\Employee;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        Employee::create([
            'first_name' => 'System',
            'last_name' => 'Administrator',
            'birth_date' => '1990-01-01',
            'gender' => 'Male',
            'email' => 'admin@example.com',
            'department' => 'Human Resources',
            'address' => 'Main Office',
            'username' => 'admin',
            'password' => 'admin12345',
            'role' => Role::Admin,
            'employment_status' => EmploymentStatus::Active,
        ]);

        $employee = Employee::create([
            'first_name' => 'Juan',
            'middle_name' => 'Santos',
            'last_name' => 'Dela Cruz',
            'birth_date' => '1998-05-14',
            'gender' => 'Male',
            'email' => 'juan@example.com',
            'department' => 'Information Technology',
            'address' => 'Manila, Philippines',
            'username' => 'employee',
            'password' => 'employee123',
            'role' => Role::Employee,
            'employment_status' => EmploymentStatus::Active,
        ]);

        $subjects = collect([
            ['ITELEC4100', 'Advanced Web Applications Development', 3],
            ['IT3101', 'Database Management Systems', 3],
            ['IT3102', 'Information Assurance and Security', 3],
            ['IT3103', 'Systems Integration and Architecture', 3],
            ['GE101', 'Understanding the Self', 3],
        ])->map(fn (array $subject) => Subject::create([
            'subject_code' => $subject[0],
            'subject_name' => $subject[1],
            'units' => $subject[2],
        ]));

        $schedules = [
            ['Monday', '08:00', '11:00', 'Lab 301'],
            ['Wednesday', '13:00', '16:00', 'Room 204'],
        ];

        foreach ($schedules as $index => [$day, $start, $end, $room]) {
            $employee->assignments()
                ->create(['subject_id' => $subjects[$index]->id])
                ->schedule()
                ->create(['day' => $day, 'start_time' => $start, 'end_time' => $end, 'room' => $room]);
        }

        $employee->tasks()->createMany([
            [
                'task_title' => 'Prepare midterm examination',
                'task_description' => 'Draft and submit the midterm exam for ITELEC4100.',
                'due_date' => now()->addWeek()->toDateString(),
                'status' => TaskStatus::Ongoing,
            ],
            [
                'task_title' => 'Submit class records',
                'task_description' => 'Encode grades into the registrar portal.',
                'due_date' => now()->addWeeks(2)->toDateString(),
                'status' => TaskStatus::Pending,
            ],
        ]);

        $employee->subjectRequests()->create([
            'subject_code' => 'IT3104',
            'subject_name' => 'Capstone Project 1',
            'units' => 3,
        ]);

        $employee->leaveRequests()->create([
            'leave_type' => 'Vacation',
            'start_date' => now()->addMonth()->toDateString(),
            'end_date' => now()->addMonth()->addDays(2)->toDateString(),
            'reason' => 'Family trip to Baguio.',
        ]);
    }
}
