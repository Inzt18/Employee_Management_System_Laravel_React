<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * "Sign in with Google" for employees whose Google email matches their account email.
 *
 * Flow: React -> /api/auth/google/redirect -> Google -> /api/auth/google/callback
 *       -> React /auth/callback#token=... (or #error=...)
 */
class GoogleAuthController extends Controller
{
    public static function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }

    public function providers(): JsonResponse
    {
        return response()->json(['google' => self::isConfigured()]);
    }

    public function redirect(): RedirectResponse
    {
        abort_unless(self::isConfigured(), 404, 'Google login is not configured.');

        return Socialite::driver('google')->stateless()->redirect();
    }

    public function callback(): RedirectResponse
    {
        abort_unless(self::isConfigured(), 404, 'Google login is not configured.');

        try {
            $googleUser = Socialite::driver('google')->stateless()->user();
        } catch (Throwable) {
            return $this->toFrontend(['error' => 'Google sign-in was cancelled or failed. Please try again.']);
        }

        $employee = Employee::where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $employee) {
            return $this->toFrontend(['error' => 'No employee account uses ' . $googleUser->getEmail() . '. Please register first.']);
        }

        if (! $employee->isActive()) {
            return $this->toFrontend(['error' => 'Your account is not active. Please contact the administrator.']);
        }

        $employee->forceFill([
            'google_id' => $googleUser->getId(),
            'avatar_url' => $googleUser->getAvatar(),
        ])->save();

        return $this->toFrontend(['token' => $employee->createToken(AuthController::TOKEN_NAME)->plainTextToken]);
    }

    /**
     * The token travels in the URL fragment so it never reaches server logs.
     */
    private function toFrontend(array $fragment): RedirectResponse
    {
        return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/auth/callback#' . http_build_query($fragment));
    }
}
