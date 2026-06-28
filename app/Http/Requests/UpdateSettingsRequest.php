<?php

namespace App\Http\Requests;

use App\Models\Tag;
use App\Rules\HasTagInEachCategory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSettingsRequest extends FormRequest
{
    /**
     * Scoped to the user's own restaurant in the controller, so the request only
     * validates shape. Slug is intentionally absent — it's read-only; the display
     * name can be edited.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            // Display name (written to the restaurant's default locale). Slug stays
            // immutable, so it is intentionally not accepted here. The regex (with
            // the /u flag) rejects interior control characters and malformed UTF-8,
            // so a hostile name can't corrupt the JSON column or 500 the save.
            'name' => ['required', 'string', 'min:2', 'max:255', 'regex:/^[^\x00-\x1F\x7F]+$/u'],

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

            // At least one tag is required in every category (cuisine, dietary,
            // vibe, style).
            'tag_ids' => ['required', 'array', 'max:50', new HasTagInEachCategory(Tag::OWNER_CATEGORIES)],
            'tag_ids.*' => ['integer', 'distinct', Rule::exists('tags', 'id')->whereIn('category', Tag::OWNER_CATEGORIES)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => __('The restaurant name is required.'),
            'name.min' => __('The restaurant name must be at least :min characters.'),
            'name.regex' => __('The restaurant name contains invalid characters.'),
            'country_code.size' => __('Please choose a country from the list.'),
            'country_code.alpha' => __('Please choose a country from the list.'),
            'phone.regex' => __('Please enter a valid phone number using digits only.'),
            'currency.in' => __('Please choose a currency from the list.'),
            'tag_ids.required' => __('Pick at least one tag in each category.'),
        ];
    }
}
