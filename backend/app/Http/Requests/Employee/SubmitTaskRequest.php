<?php

namespace App\Http\Requests\Employee;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_title' => ['required', 'string', 'max:150'],
            'task_description' => ['required', 'string', 'max:2000'],
            'due_date' => ['required', 'date', 'after_or_equal:today'],
            'task_status' => ['required', Rule::enum(TaskStatus::class)],
        ];
    }
}
