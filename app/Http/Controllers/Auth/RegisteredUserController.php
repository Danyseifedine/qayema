<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Sign-up with a username and a password. Google sign-up needs no page: its
 * callback makes the account (GoogleController).
 */
class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('portal.auth.signup');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $user = User::create([
            'name' => $request->validated('name'),
            'username' => $request->validated('username'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => UserRole::MenuOwner,
            'onboarding_step' => 0,
        ]);

        $request->countAccount();

        Auth::login($user, remember: true);
        $request->session()->regenerate();

        return redirect($user->afterLoginUrl());
    }
}
