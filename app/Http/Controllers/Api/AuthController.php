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
