<?php

namespace App\Http\Requests;

use App\Rules\ValidCaptcha;
use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Public contact form — open to guests; abuse is mitigated by rate limiting.
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:2000'],
            'g-recaptcha-response' => [new ValidCaptcha],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Please provide your email address.',
            'email.email' => 'Please provide a valid email address.',
            'message.required' => 'Please write a message.',
            'message.min' => 'Your message must be at least 10 characters.',
            'message.max' => 'Your message must not exceed 2000 characters.',
        ];
    }
}
