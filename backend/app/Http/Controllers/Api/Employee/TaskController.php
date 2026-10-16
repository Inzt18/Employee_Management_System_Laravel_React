<?php

namespace App\Http\Controllers\Api\Employee;

use App\Http\Controllers\Controller;
use App\Http\Resources\TaskResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Tasks assigned to the signed-in employee.
 */
class TaskController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $tasks = $request->user()->tasks()
            ->search($request->query('search'))
            ->orderByRaw('due_date IS NULL, due_date ASC')
            ->get();

        return TaskResource::collection($tasks);
    }
}
