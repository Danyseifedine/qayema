<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\SharedFieldRules;
use App\Services\Menu\MenuLanguages;
use App\Services\Menu\OpeningHours;
use App\Support\MenuTextLimits;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRestaurantRequest extends FormRequest
{
    use SharedFieldRules;

    public function authorize(): bool
    {
        return $this->user()?->restaurant !== null;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        // The languages themselves are set on the Features page
        // (MenuLanguagesController); the text here follows them.
        $languages = MenuLanguages::forOwner($this->user());

        $rules = [
            // One entry per menu language, English required. The slug has
            // its own call (PUT /api/restaurant/slug), so it is not taken here.
            'name' => ['required', 'array'],
            'description' => ['nullable', 'array'],
            // Where the restaurant is. A link rather than a written address:
            // it is what a guest taps for directions, and the dashboard can
            // fill it from the owner's own position.
            'google_maps_url' => ['nullable', 'url:http,https', 'max:2048'],

            // The logo is mandatory: it can be replaced (a temp-upload key) but
            // never cleared, so there is no delete flag for it.
            'logo_key' => $this->tempImageKeyRules(),
            'cover_image_key' => $this->tempImageKeyRules(),
            'delete_cover_image' => ['nullable', 'boolean'],

            'country_code' => $this->countryCodeRules(),
            'phone' => $this->phoneRules(),
            'currency' => $this->currencyRules(),
            // A second currency some dishes may be priced in, with how many
            // of the main one a unit of it is worth (89000 for 1 USD in LBP).
            'second_currency' => ['nullable', 'string', Rule::in(array_keys(config('currencies', []))), 'different:currency'],
            'exchange_rate' => ['nullable', 'required_with:second_currency', 'numeric', 'gt:0', 'max:1000000000'],

            // A list of shifts per weekday (OpeningHours::MAX_SHIFTS at
            // most), or null for a day it does not open. One range per day,
            // the shape before shifts, arrives as a list of one
            // (prepareForValidation()). Overlaps are after()'s question; the
            // service normalises before saving.
            'opening_hours' => ['nullable', 'array'],
            'opening_hours.*' => ['nullable', 'array', 'max:'.OpeningHours::MAX_SHIFTS],
            'opening_hours.*.*' => ['array'],
            'opening_hours.*.*.open' => ['required', 'date_format:H:i'],
            'opening_hours.*.*.close' => ['required', 'date_format:H:i'],
            // With the backward-compatible names: browsers still list some
            // zones only by them (Asia/Calcutta, Europe/Kiev), and the
            // dashboard offers the browser's list.
            'timezone' => ['nullable', 'string', 'timezone:all_with_bc'],
        ];

        // The main language (first) is the one a name needs.
        $rules += MenuLanguages::rules('name', $languages, MenuTextLimits::RESTAURANT_NAME, 'required', ['min:2', $this->plainTextRule()]);
        $rules += MenuLanguages::rules('description', $languages, MenuTextLimits::RESTAURANT_DESCRIPTION);

        return $rules;
    }

    /**
     * A day sent as one range, the shape before shifts (an app not yet
     * updated), becomes a list of one; one with neither time is a closed day.
     */
    protected function prepareForValidation(): void
    {
        $hours = $this->input('opening_hours');

        if (! is_array($hours)) {
            return;
        }

        foreach ($hours as $day => $value) {
            if (is_array($value) && (array_key_exists('open', $value) || array_key_exists('close', $value))) {
                $hours[$day] = blank($value['open'] ?? null) && blank($value['close'] ?? null) ? null : [$value];
            }
        }

        $this->merge(['opening_hours' => $hours]);
    }

    /**
     * A day's shifts must not overlap, and only the last may run past
     * midnight (OpeningHours::problemWith()).
     *
     * @return array<int, \Closure>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $this->ensureNoDishLosesItsCurrency($validator);

            if ($validator->errors()->has('opening_hours*')) {
                return;
            }

            foreach ((array) $this->input('opening_hours') as $day => $shifts) {
                $problem = is_array($shifts) ? OpeningHours::problemWith(array_values($shifts)) : null;

                if ($problem !== null) {
                    $validator->errors()->add("opening_hours.{$day}", __($problem));
                }
            }
        }];
    }

    /**
     * A second currency is not taken away (or swapped) while dishes are
     * still priced in it: their prices would read in the wrong money. The
     * owner moves them first. Sent only by a client that knows about it.
     */
    private function ensureNoDishLosesItsCurrency(Validator $validator): void
    {
        $restaurant = $this->user()?->restaurant;
        if ($restaurant === null || ! $this->has('second_currency') || $validator->errors()->hasAny(['currency', 'second_currency'])) {
            return;
        }

        $main = (string) $this->input('currency');
        $kept = array_filter([$main, $this->input('second_currency')]);
        // reorder(): the relation's own sort would break the grouped count on MySQL.
        $stranded = $restaurant->dishes()
            ->reorder()
            ->whereNotNull('currency')
            ->whereNotIn('currency', $kept)
            ->selectRaw('currency, count(*) as dishes')
            ->groupBy('currency')
            ->toBase()
            ->first();

        if ($stranded !== null) {
            $validator->errors()->add('second_currency', trans_choice(
                '{1} 1 dish is priced in :currency. Move it to :main first.|[2,*] :count dishes are priced in :currency. Move them to :main first.',
                (int) $stranded->dishes,
                ['count' => (int) $stranded->dishes, 'currency' => $stranded->currency, 'main' => $main],
            ));
        }
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'second_currency.in' => __('Please choose a currency from the list.'),
            'second_currency.different' => __('Pick a second currency other than the main one.'),
            'exchange_rate.required_with' => __('Enter the exchange rate.'),
            'exchange_rate.*' => __('Enter an exchange rate above 0.'),
            'opening_hours.*.max' => __('A day can have up to :max shifts.', ['max' => OpeningHours::MAX_SHIFTS]),
            'opening_hours.*.*.open.*' => __('Choose when this shift opens.'),
            'opening_hours.*.*.close.*' => __('Choose when this shift closes.'),
            ...$this->contactMessages(),
            ...MenuLanguages::requiredMessages('name', MenuLanguages::mainForOwner($this->user()), 'The restaurant name is required in your menu\'s main language (:language).'),
        ];
    }
}
