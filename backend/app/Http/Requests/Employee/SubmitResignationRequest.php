<?php

namespace App\Http\Requests\Employee;

use App\Support\DocumentRequirement;
use Illuminate\Foundation\Http\FormRequest;

class SubmitResignationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'resignation_date' => ['required', 'date', 'after_or_equal:today'],
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
            'resignation_date.after_or_equal' => 'The effective date cannot be in the past.',
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
        return DocumentRequirement::forResignation();
    }
}
