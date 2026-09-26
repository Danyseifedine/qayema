<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Written to the restaurant's default locale. The slug is immutable,
            // so it is intentionally not accepted here. The /u regex rejects
            // interior control characters and malformed UTF-8, so a hostile name
            // can't corrupt the JSON column or 500 the save.
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[^\x00-\x1F\x7F]+$/u'],
            'description' => ['nullable', 'string', 'max:2000'],
            // Where the restaurant is. A link rather than a written address:
            // it is what a guest taps for directions, and the dashboard can
            // fill it from the owner's own position.
            'google_maps_url' => ['nullable', 'url:http,https', 'max:2048'],

            // The logo is mandatory: it can be replaced (a temp-upload key) but
            // never cleared, so there is no delete flag for it.
            'logo_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
            'cover_image_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
            'delete_cover_image' => ['nullable', 'boolean'],

            // ISO-3166-1 alpha-2 — the column is char(2), so cap it at exactly two
            // ASCII letters (a longer value would 500 on save under strict mode).
            'country_code' => ['nullable', 'string', 'size:2', 'alpha:ascii'],
            // Literal space (not \s) so newlines/tabs can't be stored in the phone.
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,})[0-9+() .\-]{6,30}$/'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('currencies', [])))],

            // One range per weekday, or null for a day it does not open. The
            // service normalises before saving, so anything malformed here is
            // dropped rather than stored.
            'opening_hours' => ['nullable', 'array'],
            'opening_hours.*' => ['nullable', 'array'],
            'opening_hours.*.open' => ['required_with:opening_hours.*.close', 'nullable', 'date_format:H:i'],
            'opening_hours.*.close' => ['required_with:opening_hours.*.open', 'nullable', 'date_format:H:i'],
            'timezone' => ['nullable', 'string', 'timezone'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'country_code.size' => __('Please choose a country from the list.'),
            'country_code.alpha' => __('Please choose a country from the list.'),
            'phone.regex' => __('Please enter a valid phone number using digits only.'),
            'currency.in' => __('Please choose a currency from the list.'),
        ];
    }
}
