<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * A phone app's sign-in: an email or a username and the password, like the
 * website's, with the same lockout after five wrong tries. No captcha (an
 * app cannot show one), and no session: the answer is a token.
 */
abstract class AppLoginRequest extends LoginRequest
{
    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            // Names the token, so each phone can be told apart.
            'device_name' => ['required', 'string', 'max:100'],
        ];
    }

    /**
     * The account these credentials belong to, if `$mayUseThisApp` lets it
     * in. A wrong password, an account with no password (Google only) and an
     * account the app is not for all get the same answer, so the form never
     * tells whether an account exists.
     *
     * @param  Closure(User): bool  $mayUseThisApp
     *
     * @throws ValidationException
     */
    protected function account(Closure $mayUseThisApp): User
    {
        $this->ensureIsNotRateLimited();

        $user = User::where(User::loginCredentials($this->string('login')->value()))->first();
        $password = $this->string('password')->value();

        if ($user === null || $user->password === null || ! Hash::check($password, $user->password) || ! $mayUseThisApp($user)) {
            RateLimiter::hit($this->throttleKey());

            throw ValidationException::withMessages([
                'login' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }
}
