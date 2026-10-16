<?php

namespace App\Models;

use App\Contracts\Reviewable;
use App\Enums\TaskStatus;
use App\Models\Concerns\IsReviewable;
use Illuminate\Database\Eloquent\Model;

class TaskRequest extends Model implements Reviewable
{
    use IsReviewable;

    protected $fillable = [
        'task_title',
        'task_description',
        'due_date',
        'task_status',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'task_status' => TaskStatus::class,
        ];
    }

    public static function requestType(): string
    {
        return 'task';
    }

    public static function requestCategory(): string
    {
        return 'Task';
    }

    public function summary(): string
    {
        return "Add task {$this->task_title}";
    }

    public function details(): string
    {
        return "Title: {$this->task_title}\nDue: {$this->due_date->toDateString()}\nStatus: {$this->task_status->value}"
            . ($this->task_description ? "\n\n{$this->task_description}" : '');
    }

    public function onApproved(): void
    {
        Task::create([
            'employee_id' => $this->employee_id,
            'task_title' => $this->task_title,
            'task_description' => $this->task_description,
            'due_date' => $this->due_date,
            'status' => $this->task_status,
        ]);
    }
}
