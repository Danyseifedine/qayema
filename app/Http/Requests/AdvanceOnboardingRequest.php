<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SharedFieldRules;
use App\Models\User;
use App\Rules\AvailableSlug;
use App\Services\Menu\MenuLanguages;
use App\Support\MenuTextLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * One step of the onboarding wizard: the fields depend on `_step`, the
 * 1-based step the page shows.
 */
class AdvanceOnboardingRequest extends FormRequest
{
    use SharedFieldRules;

    public function authorize(): bool
    {
        // The route already requires a signed-in user.
        return true;
    }

    /**
     * The 0-based step being saved, clamped to a real one. Without the clamp,
     * rapid "next" clicks could push it past the final step and run the
     * wrong step's handler.
     */
    public function step(): int
    {
        return max(0, min($this->integer('_step', 1) - 1, User::ONBOARDING_STEPS - 1));
    }

    /**
     * The slug is cleaned server-side, so spaces, capitals and special
     * characters never reach validation or the database.
     */
    protected function prepareForValidation(): void
    {
        if ($this->step() === 0) {
            $this->merge(['slug' => Str::slug((string) $this->input('slug', ''))]);
        }
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // Read fresh rather than from a relation cached on the user.
        $restaurant = $this->user()?->restaurant()->first();

        return match (true) {
            // Step 1: the restaurant's name, menu link and the language the menu is written in.
            $this->step() === 0 => [
                'name' => ['required', 'string', 'min:2', 'max:'.MenuTextLimits::RESTAURANT_NAME, $this->plainTextRule()],
                'slug' => AvailableSlug::rules($restaurant?->id),
                'main_locale' => ['nullable', 'string', Rule::in(MenuLanguages::choices())],
            ],
            // Every later step edits the restaurant step 1 made; without one
            // the controller sends the owner back to step 1.
            $restaurant === null => [],
            // Step 2: country, phone and currency.
            $this->step() === 1 => [
                'country_code' => $this->countryCodeRules(),
                'phone' => $this->phoneRules(),
                'currency' => $this->currencyRules(),
                // A second currency some dishes may be priced in, and the rate
                // as the owner reads it (1 USD = 89,000 LBP; OnboardingService).
                'second_currency' => ['nullable', 'string', Rule::in(array_keys(config('currencies', []))), 'different:currency'],
                'rate' => ['nullable', 'required_with:second_currency', 'numeric', 'gt:0', 'max:1000000000'],
                // The owner's browser's timezone, sent unseen; one PHP does
                // not know is ignored rather than refused (likelyTimezone).
                'timezone' => ['nullable', 'string', 'max:64'],
            ],
            // Step 3: the logo (required until one is uploaded, so revisiting
            // the step asks for no new upload) and an optional cover.
            default => [
                'logo_key' => $this->tempImageKeyRules($restaurant->hasMedia('logo') ? 'nullable' : 'required'),
                'cover_image_key' => $this->tempImageKeyRules(),
            ],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...$this->contactMessages(),
            'second_currency.in' => __('Please choose a currency from the list.'),
            'second_currency.different' => __('Pick a second currency other than the main one.'),
            'rate.required_with' => __('Enter the exchange rate.'),
            'rate.*' => __('Enter an exchange rate above 0.'),
            'logo_key.required' => __('owner.onboarding.logo_required'),
        ];
    }
}
