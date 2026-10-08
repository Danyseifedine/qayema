<?php

namespace App\Rules;

use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A username an account may take: 3 to 30 lowercase letters, digits, dots,
 * dashes or underscores, starting and ending with a letter or digit, and not
 * another account's. Never an "@", so the sign-in box can tell a username
 * from an email. Compared lowercase, as the model stores it.
 */
class Username implements ValidationRule
{
    public const PATTERN = '/^[a-z0-9](?:[a-z0-9._-]{1,28})[a-z0-9]$/';

    public function __construct(private readonly ?int $userId = null) {}

    /**
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $username = is_string($value) ? User::normalizeUsername($value) : null;

        if ($username === null || preg_match(self::PATTERN, $username) !== 1) {
            $fail(__('A username is 3 to 30 letters, numbers, dots, dashes or underscores, starting and ending with a letter or number.'));

            return;
        }

        $taken = User::query()
            ->where('username', $username)
            ->when($this->userId, fn ($query) => $query->whereKeyNot($this->userId))
            ->exists();

        if ($taken) {
            $fail(__('That username is taken. Try another one.'));
        }
    }
}
