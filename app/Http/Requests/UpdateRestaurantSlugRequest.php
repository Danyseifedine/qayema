<?php

namespace App\Http\Requests;

use App\Rules\AvailableSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/** A new menu link, `/{slug}`, for the owner's own restaurant. */
class UpdateRestaurantSlugRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * Written the way onboarding writes one: spaces, capitals and accents
     * become a clean link before anything is checked.
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['slug' => Str::slug((string) $this->input('slug', ''))]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'slug' => [
                'required', 'string', 'min:2', 'max:100',
                'regex:/^[a-z0-9][a-z0-9-]*[a-z0-9]$/',
                new AvailableSlug($this->user()?->restaurant?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'slug.required' => __('Choose a link for your menu.'),
            'slug.min' => __('Use at least 2 letters or numbers.'),
            'slug.max' => __('Keep the link under 100 characters.'),
            'slug.regex' => __('Use letters, numbers and dashes, starting and ending with a letter or number.'),
        ];
    }
}
