<?php

namespace App\Http\Requests;

use App\Services\Global\QrStyle;
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
     * The QR design persisted to restaurants.qr_settings. The allowed shapes
     * come from QrStyle, which is what draws them, so the two cannot drift.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $colour = ['required', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'];

        return [
            'dot_style' => ['required', Rule::in(QrStyle::DOT_STYLES)],
            'dot_color' => $colour,
            // A second colour turns the dots into a gradient; null keeps them plain.
            'dot_gradient' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'gradient_type' => ['required', Rule::in(QrStyle::GRADIENT_TYPES)],
            'corner_style' => ['required', Rule::in(QrStyle::CORNER_STYLES)],
            'corner_color' => $colour,
            'eye_style' => ['required', Rule::in(QrStyle::EYE_STYLES)],
            'eye_color' => $colour,
            'background' => $colour,
            'logo' => ['required', 'boolean'],
            'logo_size' => ['required', Rule::in(array_keys(QrStyle::LOGO_SIZES))],
            'card_theme' => ['required', Rule::in(QrStyle::CARD_THEMES)],
            'title' => ['nullable', 'string', 'max:60'],
            'subtitle' => ['nullable', 'string', 'max:80'],
            'cta' => ['nullable', 'string', 'max:60'],
            'show_url' => ['required', 'boolean'],
        ];
    }
}
