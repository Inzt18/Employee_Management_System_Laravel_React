<?php

namespace App\Http\Requests\Admin;

use App\Enums\RequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(RequestStatus::decisions())],
            'admin_comment' => [
                Rule::requiredIf($this->input('status') === RequestStatus::Rejected->value),
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_comment.required' => 'Please give the employee a reason for rejecting the request.',
        ];
    }

    public function decision(): RequestStatus
    {
        return RequestStatus::from($this->validated('status'));
    }
}
