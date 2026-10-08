<?php

namespace App\Http\Requests\Admin;

use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

/**
 * A new password for a restaurant's owner, set by an admin: the way back in
 * for an owner who signed up with a username and has no email to reset it.
 */
class ResetOwnerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route already requires an admin.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'password' => ['required', 'string', Password::defaults()],
        ];
    }

    /**
     * @return array<int, \Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Restaurant $restaurant */
                $restaurant = $this->route('restaurant');
                $owner = $restaurant->user;

                // Another admin's password is never set from here.
                if ($owner === null || ! $owner->isMenuOwner()) {
                    $validator->errors()->add('password', 'This restaurant has no owner account to reset.');
                }
            },
        ];
    }
}
