<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * Usage: ->middleware('role:Admin')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $role = $request->user()?->role?->value;

        if (! in_array($role, $roles, true)) {
            return response()->json(['message' => 'You are not allowed to access this resource.'], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
