<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Subject
 */
class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_code' => $this->subject_code,
            'subject_name' => $this->subject_name,
            'units' => $this->units,
            'employees_count' => $this->whenCounted('assignments'),
        ];
    }
}
