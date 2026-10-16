<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AssignSubjectRequest;
use App\Http\Requests\Admin\SaveScheduleRequest;
use App\Http\Resources\AssignmentResource;
use App\Http\Resources\SubjectResource;
use App\Models\Employee;
use App\Models\EmployeeSubject;
use App\Models\Subject;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

/**
 * Subjects (with class schedule) assigned to a specific employee.
 */
class EmployeeSubjectController extends Controller
{
    public function index(Request $request, Employee $employee): AnonymousResourceCollection
    {
        $assignments = $employee->assignments()
            ->with(['subject', 'schedule'])
            ->search($request->query('search'))
            ->get()
            ->sortBy(fn (EmployeeSubject $assignment) => $assignment->subject->subject_code)
            ->values();

        return AssignmentResource::collection($assignments);
    }

    /**
     * Subjects that are not yet assigned to the employee.
     */
    public function available(Employee $employee): AnonymousResourceCollection
    {
        $subjects = Subject::query()
            ->whereDoesntHave('assignments', fn ($query) => $query->whereBelongsTo($employee))
            ->orderBy('subject_code')
            ->get();

        return SubjectResource::collection($subjects);
    }

    public function store(AssignSubjectRequest $request, Employee $employee): JsonResponse
    {
        $assignment = DB::transaction(function () use ($request, $employee) {
            $subjectId = $request->wantsNewSubject()
                ? Subject::create([
                    'subject_code' => $request->validated('new_subject_code'),
                    'subject_name' => $request->validated('new_subject_name'),
                    'units' => $request->validated('new_units'),
                ])->id
                : $request->validated('subject_id');

            $assignment = $employee->assignments()->create(['subject_id' => $subjectId]);
            $assignment->schedule()->create($request->scheduleData());

            return $assignment->load(['subject', 'schedule']);
        });

        return (new AssignmentResource($assignment))
            ->additional(['message' => 'Subject assigned.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(SaveScheduleRequest $request, EmployeeSubject $assignment): AssignmentResource
    {
        $assignment->schedule()->updateOrCreate([], $request->scheduleData());

        return (new AssignmentResource($assignment->load(['subject', 'schedule'])))
            ->additional(['message' => 'Schedule updated.']);
    }

    public function destroy(EmployeeSubject $assignment): JsonResponse
    {
        $assignment->delete();

        return response()->json(['message' => 'Subject removed from employee.']);
    }
}
