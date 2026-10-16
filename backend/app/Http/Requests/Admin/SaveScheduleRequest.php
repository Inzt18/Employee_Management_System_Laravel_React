<?php

namespace App\Http\Requests\Admin;

use App\Models\Schedule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'day' => ['required', Rule::in(Schedule::DAYS)],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'room' => ['required', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_time.after' => 'End time must be later than start time.',
        ];
    }

    public function scheduleData(): array
    {
        return $this->safe()->only(['day', 'start_time', 'end_time', 'room']);
    }
}
