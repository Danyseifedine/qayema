<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** "Forgot your password?": the address the reset link goes to. */
class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public page.
        return true;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
        ];
    }
}
