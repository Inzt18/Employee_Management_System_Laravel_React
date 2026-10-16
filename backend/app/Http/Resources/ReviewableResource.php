<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One shape for every request type (leave, resignation, subject, task),
 * plus the type-specific fields under "fields".
 *
 * @mixin \App\Models\LeaveRequest|\App\Models\ResignationRequest|\App\Models\SubjectRequest|\App\Models\TaskRequest
 */
class ReviewableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_type' => $this->resource::requestType(),
            'request_category' => $this->resource::requestCategory(),
            'summary' => $this->summary(),
            'details' => $this->details(),
            'fields' => $this->typeSpecificFields(),
            'status' => $this->status->value,
            'admin_comment' => $this->admin_comment,
            'employee' => $this->whenLoaded('employee', fn () => [
                'id' => $this->employee->id,
                'name' => $this->employee->sortable_name,
                'department' => $this->employee->department,
            ]),
            'reviewer' => $this->whenLoaded('reviewer', fn () => $this->reviewer?->full_name),
            'attachments' => AttachmentResource::collection($this->whenLoaded('attachments')),
            'reviewed_at' => $this->reviewed_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }

    private function typeSpecificFields(): array
    {
        return match ($this->resource::requestType()) {
            'leave' => [
                'leave_type' => $this->leave_type,
                'start_date' => $this->start_date->toDateString(),
                'end_date' => $this->end_date->toDateString(),
                'reason' => $this->reason,
            ],
            'resignation' => [
                'resignation_date' => $this->resignation_date->toDateString(),
                'reason' => $this->reason,
            ],
            'subject' => [
                'subject_code' => $this->subject_code,
                'subject_name' => $this->subject_name,
                'units' => $this->units,
            ],
            'task' => [
                'task_title' => $this->task_title,
                'task_description' => $this->task_description,
                'due_date' => $this->due_date->toDateString(),
                'task_status' => $this->task_status->value,
            ],
        };
    }
}
