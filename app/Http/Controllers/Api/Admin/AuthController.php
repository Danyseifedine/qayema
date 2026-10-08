<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\AdminLoginRequest;
use App\Http\Resources\AdminResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Signing in and out of the admin phone app. It holds a Sanctum token (the
 * dashboard's session cookie is no use to an app); the token works only on
 * `api/admin/*` and only while its account is still an admin.
 */
class AuthController extends Controller
{
    /** How long a phone stays signed in without signing in again. */
    public const TOKEN_DAYS = 60;

    public function login(AdminLoginRequest $request): JsonResponse
    {
        $admin = $request->admin();

        $token = $admin->createToken(
            $request->string('device_name')->value(),
            ['*'],
            now()->addDays(self::TOKEN_DAYS),
        );

        return response()->json([
            'token' => $token->plainTextToken,
            'expires_at' => $token->accessToken->expires_at?->toIso8601String(),
            'data' => new AdminResource($admin),
        ]);
    }

    public function me(Request $request): AdminResource
    {
        return new AdminResource($request->user());
    }

    /**
     * Ends this phone's token only; the admin's other phones stay signed in.
     */
    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()->currentAccessToken();

        // An admin's browser session reaching this has no token to end.
        if ($token instanceof PersonalAccessToken) {
            $token->delete();
        }

        return response()->json(status: 204);
    }
}
