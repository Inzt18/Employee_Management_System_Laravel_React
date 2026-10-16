<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveTaskRequest;
use App\Http\Resources\TaskResource;
use App\Models\Employee;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tasks assigned to a specific employee.
 */
class EmployeeTaskController extends Controller
{
    public function index(Request $request, Employee $employee): AnonymousResourceCollection
    {
        $tasks = $employee->tasks()
            ->search($request->query('search'))
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->get();

        return TaskResource::collection($tasks);
    }

    /**
     * Distinct titles/descriptions used before, to prefill the "add task" form.
     */
    public function templates(): JsonResponse
    {
        $templates = Task::query()
            ->select(['task_title', 'task_description'])
            ->distinct()
            ->orderBy('task_title')
            ->get();

        return response()->json(['data' => $templates]);
    }

    public function store(SaveTaskRequest $request, Employee $employee): JsonResponse
    {
        $task = $employee->tasks()->create($request->validated());

        return (new TaskResource($task))
            ->additional(['message' => 'Task assigned.'])
            ->response()
            ->setStatusCode(201);
    }

    public function update(SaveTaskRequest $request, Task $task): TaskResource
    {
        $task->update($request->validated());

        return (new TaskResource($task))->additional(['message' => 'Task updated.']);
    }

    public function destroy(Task $task): JsonResponse
    {
        $task->delete();

        return response()->json(['message' => 'Task deleted.']);
    }
}
