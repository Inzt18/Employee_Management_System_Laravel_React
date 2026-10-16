<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Signs out employees whose account was deactivated after they logged in.
 */
class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $employee = $request->user();

        if ($employee !== null && ! $employee->isActive()) {
            $employee->tokens()->delete();

            return response()->json(['message' => 'Your account is not active.'], Response::HTTP_UNAUTHORIZED);
        }

        return $next($request);
    }
}
