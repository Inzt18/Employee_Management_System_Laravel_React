<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;

class SubmitSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subject_code' => ['required', 'string', 'max:20'],
            'subject_name' => ['required', 'string', 'max:100'],
            'units' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
