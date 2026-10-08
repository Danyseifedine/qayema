<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateAccountRequest;
use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * The signed-in owner's own account. The email and the username are
 * read-only: each is how the account signs in (Google, or the username made
 * at sign-up), the identity, not a setting.
 */
class AccountController extends Controller
{
    public function update(UpdateAccountRequest $request): UserResource
    {
        $user = $request->user();

        $user->update(['name' => $request->validated('name')]);

        return new UserResource($user->fresh()->load('restaurant'));
    }

    /**
     * Change the password, or set one for the first time on a Google-only
     * account. Rotating the remember token logs every other "keep me signed
     * in" browser out; this session stays.
     */
    public function updatePassword(UpdatePasswordRequest $request): Response
    {
        $user = $request->user();

        $user->forceFill([
            'password' => Hash::make($request->validated('password')),
            'remember_token' => Str::random(60),
        ])->save();

        return response()->noContent();
    }
}
