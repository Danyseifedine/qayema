<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\OwnerLoginRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * The owner's sign-in: the dashboard rides its session cookie, the owner
 * phone app a token from `login()` that opens this API and nothing else
 * (`AppServiceProvider::keepTokensToTheirApp()`).
 */
class AuthController extends Controller
{
    /** What marks a token as the owner app's. */
    public const TOKEN_ABILITY = 'owner-app';

    /** How long a phone stays signed in without signing in again. */
    public const TOKEN_DAYS = 60;

    public function login(OwnerLoginRequest $request): JsonResponse
    {
        $owner = $request->owner();

        $token = $owner->createToken(
            $request->string('device_name')->value(),
            [self::TOKEN_ABILITY],
            now()->addDays(self::TOKEN_DAYS),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'data' => new UserResource($owner->load('restaurant')),
        ]);
    }

    /**
     * Return the current session's CSRF token in the response body.
     *
     * A cross-domain SPA (e.g. a localhost dashboard talking to qayema.test)
     * cannot read the XSRF-TOKEN cookie because it belongs to another domain, so
     * it can't echo it back on writes. Handing the token over the body (which
     * CORS lets the allow-listed origin read) lets the SPA send it as the
     * X-CSRF-TOKEN header and pass CSRF validation. Same-domain SPAs don't need
     * this (they read the cookie directly), but it's harmless for them too.
     */
    public function csrfToken(): JsonResponse
    {
        // Never cache the token: a stale token from a proxy/browser cache would
        // cause CSRF mismatches on writes.
        return response()->json(['token' => csrf_token()])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
    }

    /**
     * Return the authenticated SPA user. The dashboard calls this on boot to
     * decide whether to render or bounce the visitor to the Laravel login.
     */
    public function user(Request $request): UserResource
    {
        return new UserResource($request->user()->load('restaurant'));
    }

    /**
     * Log the user out: the owner app's token, or the SPA's session (torn
     * down, then the CSRF token rotated). Returns 204 so the SPA can handle
     * the redirect itself instead of following a server redirect.
     */
    public function logout(Request $request): JsonResponse
    {
        // The owner app: end this phone's token only; the owner's other
        // phones and their browser stay signed in.
        $token = $request->user()->currentAccessToken();
        if ($token instanceof PersonalAccessToken) {
            $token->delete();

            return response()->json(status: 204);
        }

        Auth::guard('web')->logout();

        // Stateful SPA logout requests carry a session; tear it down and rotate
        // the CSRF token. Token-based or session-less requests simply skip this.
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(status: 204);
    }
}
