<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveEmployeeRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class EmployeeController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $employees = Employee::query()
            ->search($request->query('search'))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        return EmployeeResource::collection($employees);
    }

    public function store(SaveEmployeeRequest $request): JsonResponse
    {
        $employee = Employee::create($request->employeeData());

        return (new EmployeeResource($employee))
            ->additional(['message' => 'Employee added.'])
            ->response()
            ->setStatusCode(201);
    }

    public function show(Employee $employee): EmployeeResource
    {
        return new EmployeeResource($employee);
    }

    public function update(SaveEmployeeRequest $request, Employee $employee): EmployeeResource
    {
        $data = $request->employeeData();
        $this->guardOwnAccount($request, $employee, $data);

        $employee->update($data);

        if (! $employee->isActive()) {
            $employee->tokens()->delete();
        }

        return (new EmployeeResource($employee))->additional(['message' => 'Employee updated.']);
    }

    public function destroy(Request $request, Employee $employee): JsonResponse
    {
        if ($request->user()->is($employee)) {
            throw ValidationException::withMessages(['employee' => 'You cannot delete your own account.']);
        }

        $employee->delete();

        return response()->json(['message' => 'Employee deleted.']);
    }

    /**
     * Prevent an admin from locking themselves out.
     */
    private function guardOwnAccount(Request $request, Employee $employee, array $data): void
    {
        if (! $request->user()->is($employee)) {
            return;
        }

        if (($data['role'] ?? null) !== Role::Admin->value) {
            throw ValidationException::withMessages(['role' => 'You cannot remove your own admin role.']);
        }

        if (($data['employment_status'] ?? null) !== EmploymentStatus::Active->value) {
            throw ValidationException::withMessages(['employment_status' => 'You cannot deactivate your own account.']);
        }
    }
}
