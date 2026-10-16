<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A subject assigned to an employee, flattened with its schedule.
 *
 * @mixin \App\Models\EmployeeSubject
 */
class AssignmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $schedule = $this->schedule;

        return [
            'id' => $this->id,
            'employee_id' => $this->employee_id,
            'subject' => new SubjectResource($this->subject),
            'schedule' => $schedule ? [
                'day' => $schedule->day,
                'start_time' => substr($schedule->start_time, 0, 5),
                'end_time' => substr($schedule->end_time, 0, 5),
                'room' => $schedule->room,
            ] : null,
        ];
    }
}
