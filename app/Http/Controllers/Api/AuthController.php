<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    /**
     * Return the current session's CSRF token in the response body.
     *
     * A cross-domain SPA (e.g. a localhost dashboard talking to qayema.test)
     * cannot read the XSRF-TOKEN cookie because it belongs to another domain, so
     * it can't echo it back on writes. Handing the token over the body — which
     * CORS lets the allow-listed origin read — lets the SPA send it as the
     * X-CSRF-TOKEN header and pass CSRF validation. Same-domain SPAs don't need
     * this (they read the cookie directly), but it's harmless for them too.
     */
    public function csrfToken(Request $request): JsonResponse
    {
        return response()->json(['token' => csrf_token()]);
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
     * Log the SPA user out by tearing down the session that backs the Sanctum
     * stateful cookie, then rotating the CSRF token. Returns 204 so the SPA can
     * handle the redirect itself instead of following a server redirect.
     */
    public function logout(Request $request): JsonResponse
    {
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
