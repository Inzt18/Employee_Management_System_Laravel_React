<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\AssignmentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * The signed-in employee's subjects and class schedule.
 */
class ScheduleController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $assignments = $request->user()->assignments()
            ->with(['subject', 'schedule'])
            ->search($request->query('search'))
            ->get()
            ->sortBy(fn ($assignment) => $assignment->subject->subject_code)
            ->values();

        return AssignmentResource::collection($assignments);
    }
}
