<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The owner phone app's sign-in (`POST /api/login`). Restaurant owners
 * only: an admin's account gets the same "not correct" as a wrong password.
 */
class OwnerLoginRequest extends AppLoginRequest
{
    /**
     * @throws ValidationException
     */
    public function owner(): User
    {
        return $this->account(fn (User $user): bool => $user->isMenuOwner());
    }
}
