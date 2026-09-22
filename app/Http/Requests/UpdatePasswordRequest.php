<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class UpdatePasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $rules = [
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];

        // A Google-only account is *setting* a password, so there is no current
        // one to check. Everyone else must prove they know the old one.
        if ($this->user()?->password !== null) {
            $rules['current_password'] = ['required', 'string', 'current_password'];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.current_password' => __('The current password is incorrect.'),
        ];
    }
}
