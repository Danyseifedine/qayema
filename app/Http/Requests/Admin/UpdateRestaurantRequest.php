<?php

namespace App\Http\Requests\Admin;

use App\Models\Restaurant;
use App\Rules\AvailableSlug;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

/**
 * A restaurant's basics from the admin app: its name (in English, the
 * language every menu has; the others are kept), its menu link (the old one
 * keeps forwarding) and its phone number (the country stays as it is).
 */
class UpdateRestaurantRequest extends FormRequest
{
    public function authorize(): bool
    {
        // The route already requires an admin.
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug((string) $this->input('slug')),
            'phone' => trim((string) $this->input('phone')) ?: null,
        ]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        /** @var Restaurant $restaurant */
        $restaurant = $this->route('restaurant');

        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'min:2', 'max:255', new AvailableSlug($restaurant->id)],
            // The same shape the owner's dashboard accepts.
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,})[0-9+() .\-]{6,30}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'Enter a phone number with at least 6 digits.',
        ];
    }
}
