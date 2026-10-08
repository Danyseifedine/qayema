<?php

namespace App\Http\Requests\Auth;

use App\Rules\Username;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * A new account made with a username and a password, the other way in next
 * to Google. The email is optional: with one, the owner can also sign in
 * with it and reset a lost password themself; without one, an admin sets a
 * new password.
 */
class RegisterRequest extends FormRequest
{
    /** Accounts one address may create in an hour. */
    public const PER_HOUR = 5;

    public function authorize(): bool
    {
        // Public sign-up page.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:30', new Username],
            'email' => ['nullable', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * Stops one address making accounts in bulk. Counted on each account
     * made (`countAccount()`), never on a mistyped form.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), self::PER_HOUR)) {
            return;
        }

        throw ValidationException::withMessages([
            'username' => __('auth.signup.throttle', [
                'minutes' => (int) ceil(RateLimiter::availableIn($this->throttleKey()) / 60),
            ]),
        ]);
    }

    public function countAccount(): void
    {
        RateLimiter::hit($this->throttleKey(), 3600);
    }

    private function throttleKey(): string
    {
        return 'signup|'.$this->ip();
    }
}
