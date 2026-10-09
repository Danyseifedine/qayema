<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Validation\ValidationException;

/**
 * The admin phone app's sign-in (`POST /api/admin/login`).
 */
class AdminLoginRequest extends AppLoginRequest
{
    /**
     * @throws ValidationException
     */
    public function admin(): User
    {
        return $this->account(fn (User $user): bool => $user->isAdmin());
    }
}
