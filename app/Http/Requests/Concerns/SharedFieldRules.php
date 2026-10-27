<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Validation\Rule;

/**
 * Field rules several forms share (onboarding, the owner's restaurant and
 * account, dishes, the admin app), kept once so they never drift apart.
 */
trait SharedFieldRules
{
    /**
     * A temp-upload key (MediaService), the only way an image arrives: never
     * a raw file, never a path.
     *
     * @return array<int, string>
     */
    protected function tempImageKeyRules(string $presence = 'nullable'): array
    {
        return [$presence, 'string', 'regex:/^[a-f0-9\-]{36}$/'];
    }

    /**
     * No control characters, and valid UTF-8 (the /u), so a hostile name
     * can't corrupt a JSON column or a mail header, or 500 the save.
     */
    protected function plainTextRule(): string
    {
        return 'regex:/^[^\x00-\x1F\x7F]+$/u';
    }

    /**
     * At least six digits; a literal space (not \s), so newlines and tabs
     * can't be stored.
     *
     * @return array<int, string>
     */
    protected function phoneRules(string $presence = 'required'): array
    {
        return [$presence, 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,})[0-9+() .\-]{6,30}$/'];
    }

    /**
     * ISO-3166-1 alpha-2: the column is char(2), so exactly two ASCII letters
     * (a longer value would 500 on save under strict mode).
     *
     * @return array<int, string>
     */
    protected function countryCodeRules(): array
    {
        return ['nullable', 'string', 'size:2', 'alpha:ascii'];
    }

    /**
     * @return array<int, mixed>
     */
    protected function currencyRules(): array
    {
        return ['required', 'string', Rule::in(array_keys(config('currencies', [])))];
    }

    /**
     * The currency a dish is priced in: the menu's main one or its second
     * one (Restaurant::currencyOf()); its variants and add-ons with it. The
     * dish requests and an offer's deal dish (StoreOfferRequest) share it.
     *
     * @return array<int, mixed>
     */
    protected function dishCurrencyRules(): array
    {
        $restaurant = $this->user()?->restaurant;

        return ['nullable', 'string', Rule::in(array_filter([$restaurant?->currency, $restaurant?->second_currency]))];
    }

    /**
     * The owner-facing messages for the country, phone and currency rules.
     *
     * @return array<string, string>
     */
    protected function contactMessages(): array
    {
        return [
            'country_code.size' => __('Please choose a country from the list.'),
            'country_code.alpha' => __('Please choose a country from the list.'),
            'phone.regex' => __('Please enter a valid phone number using digits only.'),
            'currency.in' => __('Please choose a currency from the list.'),
        ];
    }
}
