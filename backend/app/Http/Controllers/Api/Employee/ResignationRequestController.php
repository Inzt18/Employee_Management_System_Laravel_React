<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\SubmitResignationRequest;
use App\Http\Resources\ReviewableResource;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ResignationRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $resignationRequests = $request->user()->resignationRequests()
            ->with(['attachments', 'reviewer'])
            ->search($request->query('search'))
            ->latest()
            ->get();

        return ReviewableResource::collection($resignationRequests);
    }

    public function store(SubmitResignationRequest $request, AttachmentService $attachments): JsonResponse
    {
        $resignationRequest = $attachments->createWithAttachments(
            fn () => $request->user()->resignationRequests()->create(
                $request->safe()->only(['resignation_date', 'reason'])
            ),
            $request,
            $request->documentRequirements(),
        );

        return (new ReviewableResource($resignationRequest))
            ->additional(['message' => 'Resignation request submitted for admin approval.'])
            ->response()
            ->setStatusCode(201);
    }
}
