<?php

namespace App\Http\Requests;

use App\Services\Menu\MenuLanguages;
use App\Services\Menu\OpeningHours;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateRestaurantRequest extends FormRequest
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
            'logo_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
            'cover_image_key' => ['nullable', 'string', 'regex:/^[a-f0-9\-]{36}$/'],
            'delete_cover_image' => ['nullable', 'boolean'],

            // ISO-3166-1 alpha-2: the column is char(2), so cap it at exactly two
            // ASCII letters (a longer value would 500 on save under strict mode).
            'country_code' => ['nullable', 'string', 'size:2', 'alpha:ascii'],
            // Literal space (not \s) so newlines/tabs can't be stored in the phone.
            'phone' => ['required', 'string', 'max:30', 'regex:/^(?=(?:\D*\d){6,})[0-9+() .\-]{6,30}$/'],
            'currency' => ['required', 'string', Rule::in(array_keys(config('currencies', [])))],

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

        // The /u regex rejects interior control characters and malformed
        // UTF-8, so a hostile name can't corrupt the JSON column or 500 the
        // save. The main language (first) is the one a name needs.
        $rules += MenuLanguages::rules('name', $languages, 255, 'required', ['min:2', 'regex:/^[^\x00-\x1F\x7F]+$/u']);
        $rules += MenuLanguages::rules('description', $languages, 2000);

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
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'opening_hours.*.max' => __('A day can have up to :max shifts.', ['max' => OpeningHours::MAX_SHIFTS]),
            'opening_hours.*.*.open.*' => __('Choose when this shift opens.'),
            'opening_hours.*.*.close.*' => __('Choose when this shift closes.'),
            'country_code.size' => __('Please choose a country from the list.'),
            'country_code.alpha' => __('Please choose a country from the list.'),
            'phone.regex' => __('Please enter a valid phone number using digits only.'),
            'currency.in' => __('Please choose a currency from the list.'),
            ...MenuLanguages::requiredMessages('name', MenuLanguages::mainForOwner($this->user()), 'The restaurant name is required in your menu\'s main language (:language).'),
        ];
    }
}
