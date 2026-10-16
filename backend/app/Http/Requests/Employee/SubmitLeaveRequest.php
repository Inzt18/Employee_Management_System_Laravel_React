<?php

namespace App\Http\Requests\Employee;

use App\Enums\LeaveType;
use App\Support\DocumentRequirement;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubmitLeaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'leave_type' => ['required', Rule::enum(LeaveType::class)],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
        ];

        foreach ($this->documentRequirements() as $requirement) {
            $rules[$requirement->field()] = $requirement->rules();
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'start_date.after_or_equal' => 'Leave cannot start in the past.',
            'end_date.after_or_equal' => 'End date cannot be earlier than start date.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [];
        foreach ($this->documentRequirements() as $requirement) {
            $attributes[$requirement->field()] = $requirement->label;
        }

        return $attributes;
    }

    /**
     * @return DocumentRequirement[]
     */
    public function documentRequirements(): array
    {
        return LeaveType::tryFrom((string) $this->input('leave_type'))?->documentRequirements() ?? [];
    }
}
