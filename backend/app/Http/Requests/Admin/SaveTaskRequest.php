<?php

namespace App\Http\Requests\Admin;

use App\Enums\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'task_title' => ['required', 'string', 'max:150'],
            'task_description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
            'status' => ['required', Rule::enum(TaskStatus::class)],
        ];
    }
}
