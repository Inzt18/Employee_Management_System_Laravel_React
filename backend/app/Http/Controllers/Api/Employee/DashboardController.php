<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\EmployeeResource;
use App\Services\RequestReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request, RequestReviewService $reviews): JsonResponse
    {
        $employee = $request->user()->loadCount(['assignments', 'tasks', 'leaveRequests', 'resignationRequests']);

        return response()->json([
            'employee' => new EmployeeResource($employee),
            'totals' => [
                'subjects' => $employee->assignments_count,
                'tasks' => $employee->tasks_count,
                'leave_requests' => $employee->leave_requests_count,
                'resignation_requests' => $employee->resignation_requests_count,
            ],
            'pending' => $reviews->pendingCountsFor($employee),
        ]);
    }
}
