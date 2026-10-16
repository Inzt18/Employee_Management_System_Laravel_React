<?php

namespace App\Http\Controllers\Api\Auth;

use App\Enums\EmploymentStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\EmployeeResource;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public const TOKEN_NAME = 'spa';

    /**
     * Self-registration is limited to the Employee role.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $employee = Employee::create([
            ...$request->safe()->except('password_confirmation'),
            'role' => Role::Employee,
            'employment_status' => EmploymentStatus::Active,
        ]);

        return $this->tokenResponse($employee, 'Account created successfully.', Response::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $employee = Employee::where('username', $request->validated('username'))->first();

        if (! $employee || ! Hash::check($request->validated('password'), $employee->password)) {
            throw ValidationException::withMessages([
                'username' => 'Invalid username or password.',
            ]);
        }

        if (! $employee->isActive()) {
            return response()->json(['message' => 'Your account is not active. Please contact the administrator.'], Response::HTTP_FORBIDDEN);
        }

        return $this->tokenResponse($employee, 'Signed in successfully.');
    }

    public function me(Request $request): EmployeeResource
    {
        return new EmployeeResource($request->user());
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Signed out.']);
    }

    private function tokenResponse(Employee $employee, string $message, int $status = Response::HTTP_OK): JsonResponse
    {
        return response()->json([
            'message' => $message,
            'token' => $employee->createToken(self::TOKEN_NAME)->plainTextToken,
            'employee' => new EmployeeResource($employee),
        ], $status);
    }
}
