<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_code' => ['required', 'string', 'max:20', Rule::unique('subjects', 'subject_code')->ignore($this->route('subject'))],
            'subject_name' => ['required', 'string', 'max:100'],
            'units' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'subject_code.unique' => 'A subject with this code already exists.',
        ];
    }
}
