<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

/**
 * "Sign in with Google" and "Sign in with Facebook" for employees whose
 * social account email matches their employee account email.
 *
 * Flow: React -> /api/auth/{provider}/redirect -> Google or Facebook
 *       -> /api/auth/{provider}/callback -> React /auth/callback#token=... (or #error=...)
 */
class SocialAuthController extends Controller
{
    /**
     * Supported providers: route name => [display label, employees column that stores the provider's user id].
     */
    public const PROVIDERS = [
        'google' => ['label' => 'Google', 'column' => 'google_id'],
        'facebook' => ['label' => 'Facebook', 'column' => 'facebook_id'],
    ];

    public static function isConfigured(string $provider): bool
    {
        return filled(config("services.{$provider}.client_id")) && filled(config("services.{$provider}.client_secret"));
    }

    /**
     * Tells the login page which social buttons to show, e.g. {"google": true, "facebook": false}.
     */
    public function providers(): JsonResponse
    {
        return response()->json(
            collect(array_keys(self::PROVIDERS))->mapWithKeys(fn ($provider) => [$provider => self::isConfigured($provider)])
        );
    }

    public function redirect(string $provider): RedirectResponse
    {
        $this->ensureConfigured($provider);

        $driver = Socialite::driver($provider)->stateless();

        if ($provider === 'facebook') {
            $driver->scopes(['email']);
        }

        return $driver->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        $this->ensureConfigured($provider);
        ['label' => $label, 'column' => $column] = self::PROVIDERS[$provider];

        try {
            $socialUser = Socialite::driver($provider)->stateless()->user();
        } catch (Throwable) {
            return $this->toFrontend(['error' => "{$label} sign-in was cancelled or failed. Please try again."]);
        }

        $email = $socialUser->getEmail();

        $employee = Employee::where($column, $socialUser->getId())
            ->when($email, fn ($query) => $query->orWhere('email', $email))
            ->first();

        if (! $employee) {
            return $this->toFrontend(['error' => $email
                ? "No employee account uses {$email}. Please register first."
                : "Your {$label} account did not share an email address, so we could not find your employee account."]);
        }

        if (! $employee->isActive()) {
            return $this->toFrontend(['error' => 'Your account is not active. Please contact the administrator.']);
        }

        $employee->forceFill([
            $column => $socialUser->getId(),
            'avatar_url' => $socialUser->getAvatar() ?? $employee->avatar_url,
        ])->save();

        return $this->toFrontend(['token' => $employee->createToken(AuthController::TOKEN_NAME)->plainTextToken]);
    }

    private function ensureConfigured(string $provider): void
    {
        abort_unless(self::isConfigured($provider), 404, ucfirst($provider) . ' login is not configured.');
    }

    /**
     * The token travels in the URL fragment so it never reaches server logs.
     */
    private function toFrontend(array $fragment): RedirectResponse
    {
        return redirect()->away(rtrim(config('app.frontend_url'), '/') . '/auth/callback#' . http_build_query($fragment));
    }
}
