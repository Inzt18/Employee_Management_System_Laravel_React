<?php

use App\Enums\Role;
use App\Http\Controllers\Api\Admin;
use App\Http\Controllers\Api\AttachmentController;
use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\SocialAuthController;
use App\Http\Controllers\Api\Employee;
use App\Http\Controllers\Api\OptionsController;
use App\Support\RequestRegistry;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public routes
|--------------------------------------------------------------------------
*/
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    // Social login (Google and Facebook)
    Route::get('providers', [SocialAuthController::class, 'providers']);
    Route::get('{provider}/redirect', [SocialAuthController::class, 'redirect'])
        ->whereIn('provider', array_keys(SocialAuthController::PROVIDERS));
    Route::get('{provider}/callback', [SocialAuthController::class, 'callback'])
        ->whereIn('provider', array_keys(SocialAuthController::PROVIDERS));
});

Route::get('options', OptionsController::class);

/*
|--------------------------------------------------------------------------
| Authenticated routes (Bearer token issued by Sanctum)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::get('auth/me', [AuthController::class, 'me']);
    Route::post('auth/logout', [AuthController::class, 'logout']);

    Route::get('attachments/{attachment}', [AttachmentController::class, 'show']);

    // Employee self-service
    Route::prefix('me')->middleware('role:' . Role::Employee->value)->group(function () {
        Route::get('dashboard', Employee\DashboardController::class);
        Route::get('schedule', [Employee\ScheduleController::class, 'index']);
        Route::get('tasks', [Employee\TaskController::class, 'index']);

        Route::apiResource('leave-requests', Employee\LeaveRequestController::class)->only(['index', 'store']);
        Route::apiResource('resignation-requests', Employee\ResignationRequestController::class)->only(['index', 'store']);
        Route::apiResource('subject-requests', Employee\SubjectRequestController::class)->only(['index', 'store']);
        Route::apiResource('task-requests', Employee\TaskRequestController::class)->only(['index', 'store']);
    });

    // Administration
    Route::prefix('admin')->middleware('role:' . Role::Admin->value)->group(function () {
        Route::get('dashboard', Admin\DashboardController::class);

        Route::apiResource('employees', Admin\EmployeeController::class);
        Route::apiResource('subjects', Admin\SubjectController::class)->except('show');

        Route::get('employees/{employee}/subjects', [Admin\EmployeeSubjectController::class, 'index']);
        Route::get('employees/{employee}/available-subjects', [Admin\EmployeeSubjectController::class, 'available']);
        Route::post('employees/{employee}/subjects', [Admin\EmployeeSubjectController::class, 'store']);
        Route::put('assignments/{assignment}', [Admin\EmployeeSubjectController::class, 'update']);
        Route::delete('assignments/{assignment}', [Admin\EmployeeSubjectController::class, 'destroy']);

        Route::get('task-templates', [Admin\EmployeeTaskController::class, 'templates']);
        Route::get('employees/{employee}/tasks', [Admin\EmployeeTaskController::class, 'index']);
        Route::post('employees/{employee}/tasks', [Admin\EmployeeTaskController::class, 'store']);
        Route::put('tasks/{task}', [Admin\EmployeeTaskController::class, 'update']);
        Route::delete('tasks/{task}', [Admin\EmployeeTaskController::class, 'destroy']);

        Route::get('requests', [Admin\RequestController::class, 'index']);
        Route::get('requests/{type}/{id}', [Admin\RequestController::class, 'show'])
            ->whereIn('type', RequestRegistry::types())->whereNumber('id');
        Route::patch('requests/{type}/{id}', [Admin\RequestController::class, 'update'])
            ->whereIn('type', RequestRegistry::types())->whereNumber('id');
    });
});
