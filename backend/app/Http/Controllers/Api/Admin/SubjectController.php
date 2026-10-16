<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveSubjectRequest;
use App\Http\Resources\SubjectResource;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class SubjectController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $subjects = Subject::query()
            ->withCount('assignments')
            ->search($request->query('search'))
            ->orderBy('subject_code')
            ->get();

        return SubjectResource::collection($subjects);
    }

    public function store(SaveSubjectRequest $request): JsonResponse
    {
        $subject = Subject::create($request->validated());

        return (new SubjectResource($subject))
            ->additional(['message' => 'Subject added.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(SaveSubjectRequest $request, Subject $subject): SubjectResource
    {
        $subject->update($request->validated());

        return (new SubjectResource($subject))->additional(['message' => 'Subject updated.']);
    }

    /**
     * Assignments and their schedules are removed by the cascading foreign keys.
     */
    public function destroy(Subject $subject): JsonResponse
    {
        $subject->delete();

        return response()->json(['message' => 'Subject deleted.']);
    }
}
