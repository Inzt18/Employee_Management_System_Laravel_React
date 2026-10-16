<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Employee
 */
class EmployeeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'sortable_name' => $this->sortable_name,
            'birth_date' => $this->birth_date?->toDateString(),
            'gender' => $this->gender,
            'email' => $this->email,
            'department' => $this->department,
            'address' => $this->address,
            'username' => $this->username,
            'role' => $this->role->value,
            'employment_status' => $this->employment_status->value,
            'avatar_url' => $this->avatar_url,
            'has_google' => $this->google_id !== null,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
