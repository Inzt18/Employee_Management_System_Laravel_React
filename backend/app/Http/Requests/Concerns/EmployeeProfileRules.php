<?php

namespace App\Http\Requests\Concerns;

use App\Models\Employee;
use Illuminate\Validation\Rule;

/**
 * Validation rules shared by self-registration and the admin employee form.
 */
trait EmployeeProfileRules
{
    protected function profileRules(?Employee $ignore = null): array
    {
        return [
            'first_name' => ['required', 'string', 'max:50'],
            'middle_name' => ['nullable', 'string', 'max:50'],
            'last_name' => ['required', 'string', 'max:50'],
            'birth_date' => ['required', 'date', 'before:today'],
            'gender' => ['required', Rule::in(Employee::GENDERS)],
            'email' => ['required', 'email', 'max:100', Rule::unique('employees', 'email')->ignore($ignore)],
            'department' => ['required', 'string', 'max:100'],
            'address' => ['required', 'string', 'max:500'],
            'username' => ['required', 'string', 'regex:/^[A-Za-z0-9._-]+$/', 'max:50', Rule::unique('employees', 'username')->ignore($ignore)],
        ];
    }

    protected function profileMessages(): array
    {
        return [
            'email.unique' => 'That email is already registered.',
            'username.unique' => 'That username is already taken.',
            'username.regex' => 'Username may only contain letters, numbers, dots, dashes and underscores.',
            'birth_date.before' => 'Date of birth must be in the past.',
        ];
    }
}
