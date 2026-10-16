<?php

namespace App\Http\Controllers\Api;

use App\Enums\EmploymentStatus;
use App\Enums\LeaveType;
use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Schedule;
use App\Support\DocumentRequirement;
use Illuminate\Http\JsonResponse;

/**
 * Lookup values for dropdowns, so the frontend never hard-codes them.
 */
class OptionsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'departments' => Employee::DEPARTMENTS,
            'genders' => Employee::GENDERS,
            'roles' => Role::values(),
            'employment_statuses' => EmploymentStatus::values(),
            'task_statuses' => TaskStatus::values(),
            'days' => Schedule::DAYS,
            'leave_types' => array_map(fn (LeaveType $type) => [
                'value' => $type->value,
                'label' => $type->label(),
                'documents' => $type->documentRequirements(),
            ], LeaveType::cases()),
            'resignation_documents' => DocumentRequirement::forResignation(),
            'upload' => [
                'max_kilobytes' => DocumentRequirement::MAX_KILOBYTES,
                'extensions' => DocumentRequirement::ALLOWED_EXTENSIONS,
            ],
        ]);
    }
}
