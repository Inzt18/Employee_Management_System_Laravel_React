<?php

namespace App\Http\Requests\Admin;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Http\Requests\Concerns\EmployeeProfileRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Used for both creating (POST) and updating (PUT) an employee.
 * On update the password is optional and is only changed when provided.
 */
class SaveEmployeeRequest extends FormRequest
{
    use EmployeeProfileRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $employee = $this->route('employee');

        return $this->profileRules($employee) + [
            'password' => [$employee ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::enum(Role::class)],
            'employment_status' => ['required', Rule::enum(EmploymentStatus::class)],
        ];
    }

    public function messages(): array
    {
        return $this->profileMessages();
    }

    /**
     * Validated data without an empty password, so updates keep the current one.
     */
    public function employeeData(): array
    {
        return array_filter(
            $this->validated(),
            fn ($value, $key) => $key !== 'password' || filled($value),
            ARRAY_FILTER_USE_BOTH,
        );
    }
}
