<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\SubmitLeaveRequest;
use App\Http\Resources\ReviewableResource;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeaveRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $leaveRequests = $request->user()->leaveRequests()
            ->with(['attachments', 'reviewer'])
            ->search($request->query('search'))
            ->latest()
            ->get();

        return ReviewableResource::collection($leaveRequests);
    }

    public function store(SubmitLeaveRequest $request, AttachmentService $attachments): JsonResponse
    {
        $leaveRequest = $attachments->createWithAttachments(
            fn () => $request->user()->leaveRequests()->create(
                $request->safe()->only(['leave_type', 'start_date', 'end_date', 'reason'])
            ),
            $request,
            $request->documentRequirements(),
        );

        return (new ReviewableResource($leaveRequest))
            ->additional(['message' => 'Leave request submitted for admin approval.'])
            ->response()
            ->setStatusCode(201);
    }
}
