<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EmploymentStatus;
use App\Enums\RequestStatus;
use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Subject;
use App\Models\Task;
use App\Support\RequestRegistry;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $pending = [];
        foreach (RequestRegistry::map() as $type => $model) {
            $pending[$type] = $model::query()->withStatus(RequestStatus::Pending->value)->count();
        }

        return response()->json([
            'employees' => Employee::count(),
            'active_employees' => Employee::where('employment_status', EmploymentStatus::Active)->count(),
            'subjects' => Subject::count(),
            'tasks' => Task::count(),
            'pending' => $pending + ['total' => array_sum($pending)],
            'departments' => Employee::query()
                ->selectRaw('department, COUNT(*) as total')
                ->groupBy('department')
                ->orderByDesc('total')
                ->pluck('total', 'department'),
        ]);
    }
}
