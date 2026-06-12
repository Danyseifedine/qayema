<?php

namespace App\Http\Requests;

use App\Models\Restaurant;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user !== null && ($user->isMenuOwner() || $user->isAdmin());
    }

    /**
     * Normalize the slug before validation so spaces, uppercase or special
     * characters can never reach the regex rule or the database as-is.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('slug')) {
            $this->merge(['slug' => Str::slug((string) $this->input('slug'))]);
        }
    }

    public function rules(): array
    {
        $restaurant = $this->user()->restaurant;
        $hasLogo = $restaurant && $restaurant->hasMedia('logo');

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/',
                Rule::unique(Restaurant::class, 'slug')->ignore($restaurant?->id),
            ],
            'country_code' => ['nullable', 'string', 'max:5'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,})[0-9+()\s.\-]{6,30}$/'],
            'address' => ['nullable', 'string', 'max:500'],
            'google_maps_url' => ['nullable', 'url', 'max:2048'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('currencies', [])))],
            'preferred_language' => ['required', 'string', 'in:'.implode(',', config('locales.supported', ['en']))],
            'logo_key' => [$hasLogo ? 'nullable' : 'required', 'string'],
            'cover_image_key' => ['nullable', 'string'],
            'delete_logo' => ['nullable', 'boolean'],
            'delete_cover_image' => ['nullable', 'boolean'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.required' => __('menu_owner.restaurant.validation.phone_required'),
            'phone.regex' => __('menu_owner.restaurant.validation.phone_invalid'),
            'currency.required' => __('menu_owner.restaurant.validation.currency_required'),
            'currency.in' => __('menu_owner.restaurant.validation.currency_invalid'),
            'preferred_language.required' => __('menu_owner.restaurant.validation.language_required'),
            'preferred_language.in' => __('menu_owner.restaurant.validation.language_required'),
            'logo_key.required' => __('menu_owner.restaurant.validation.logo_required'),
        ];
    }
}
