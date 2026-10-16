<?php

namespace App\Http\Requests\Admin;

use Illuminate\Validation\Rule;

/**
 * Assign an existing subject (subject_id) or create a new one on the fly
 * (new_subject_code / new_subject_name / new_units), together with its schedule.
 */
class AssignSubjectRequest extends SaveScheduleRequest
{
    public function rules(): array
    {
        $employee = $this->route('employee');

        return parent::rules() + [
            'subject_id' => [
                'nullable',
                'required_without:new_subject_code',
                'integer',
                'exists:subjects,id',
                Rule::unique('employee_subjects', 'subject_id')->where('employee_id', $employee->id),
            ],
            'new_subject_code' => ['nullable', 'required_without:subject_id', 'string', 'max:20', 'unique:subjects,subject_code'],
            'new_subject_name' => ['nullable', 'required_with:new_subject_code', 'string', 'max:100'],
            'new_units' => ['nullable', 'required_with:new_subject_code', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'subject_id.required_without' => 'Choose an existing subject or create a new one.',
            'subject_id.unique' => 'This subject is already assigned to the employee.',
            'new_subject_code.required_without' => 'Choose an existing subject or create a new one.',
            'new_subject_code.unique' => 'A subject with this code already exists. Select it from the list instead.',
        ];
    }

    public function wantsNewSubject(): bool
    {
        return blank($this->validated('subject_id'));
    }
}
