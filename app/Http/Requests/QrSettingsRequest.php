<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class QrSettingsRequest extends FormRequest
{
    /**
     * Scoped to the authenticated owner's own restaurant in the controller,
     * which also enforces the qr_studio entitlement before saving.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * The QR studio design settings persisted to restaurants.qr_settings.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'bg' => ['required', Rule::in(['cream', 'ink', 'gold', 'olive'])],
            'dot' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'eye' => ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'dot_style' => ['required', Rule::in(['square', 'rounded', 'dot'])],
            'corner' => ['required', Rule::in(['sharp', 'round', 'pill'])],
            'logo' => ['required', Rule::in(['none', 'image'])],
            'show_url' => ['required', 'boolean'],
            'name' => ['nullable', 'string', 'max:60'],
            'tagline' => ['nullable', 'string', 'max:80'],
            'cta' => ['nullable', 'string', 'max:60'],
        ];
    }
}
