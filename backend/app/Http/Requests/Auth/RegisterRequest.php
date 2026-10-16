<?php

namespace App\Http\Requests\Auth;

use App\Http\Requests\Concerns\EmployeeProfileRules;
use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    use EmployeeProfileRules;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return $this->profileRules() + [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    public function messages(): array
    {
        return $this->profileMessages();
    }
}
