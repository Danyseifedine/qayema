<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * The admin phone app's sign-in (`POST /api/admin/login`): an email or a
 * username and the password, like the website's, with the same lockout
 * after five wrong tries. No captcha (an app cannot show one), and no
 * session: the answer is a token.
 */
class AdminLoginRequest extends LoginRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            // Names the token, so the admin can tell their phones apart.
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * The admin these credentials belong to. A wrong password, an account
     * with no password (Google only) and an owner's account all get the same
     * answer, so the form never tells whether an account exists.
     *
     * @throws ValidationException
     */
    public function admin(): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::where(User::loginCredentials($this->string('login')->value()))->first();
        $password = $this->string('password')->value();

        if ($user === null || $user->password === null || ! Hash::check($password, $user->password) || ! $user->isAdmin()) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }
}
