<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\SubmitSubjectRequest;
use App\Http\Resources\ReviewableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ReviewableResource::collection(
            $request->user()->subjectRequests()->with('reviewer')->latest()->get()
        );
    }

    public function store(SubmitSubjectRequest $request): JsonResponse
    {
        $subjectRequest = $request->user()->subjectRequests()->create($request->validated());

        return (new ReviewableResource($subjectRequest))
            ->additional(['message' => 'Subject request submitted for admin approval.'])
            ->response()
            ->setStatusCode(201);
    }
}
