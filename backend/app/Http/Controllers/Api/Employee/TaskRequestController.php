<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\SubmitTaskRequest;
use App\Http\Resources\ReviewableResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TaskRequestController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return ReviewableResource::collection(
            $request->user()->taskRequests()->with('reviewer')->latest()->get()
        );
    }

    public function store(SubmitTaskRequest $request): JsonResponse
    {
        $taskRequest = $request->user()->taskRequests()->create($request->validated());

        return (new ReviewableResource($taskRequest))
            ->additional(['message' => 'Task request submitted for admin approval.'])
            ->response()
            ->setStatusCode(201);
    }
}
