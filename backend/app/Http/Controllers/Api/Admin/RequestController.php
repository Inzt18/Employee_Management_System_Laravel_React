<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewRequest;
use App\Http\Resources\ReviewableResource;
use App\Services\RequestReviewService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The admin inbox: leave, resignation, subject and task requests in one list.
 */
class RequestController extends Controller
{
    public function __construct(private readonly RequestReviewService $reviews)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return ReviewableResource::collection($this->reviews->list($request->query('status')));
    }

    public function show(string $type, int $id): ReviewableResource
    {
        $request = $this->reviews->find($type, $id)->load(['employee', 'reviewer', 'attachments']);

        return new ReviewableResource($request);
    }

    public function update(ReviewRequest $request, string $type, int $id): ReviewableResource
    {
        $reviewed = $this->reviews->review(
            $this->reviews->find($type, $id),
            $request->decision(),
            $request->user(),
            $request->validated('admin_comment'),
        );

        return (new ReviewableResource($reviewed))
            ->additional(['message' => "Request {$request->decision()->value}."]);
    }
}
