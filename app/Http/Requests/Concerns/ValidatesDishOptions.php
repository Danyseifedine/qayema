<?php

namespace App\Http\Requests\Concerns;

use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Illuminate\Validation\Validator;

/**
 * A dish's variants and add-ons, as the dashboard's dish form sends them.
 *
 * Each list is validated only while the restaurant shows it (the package
 * includes it and the owner has not switched it off). Otherwise the list is
 * left out of `validated()`, so the dish's saved variants or add-ons stay as
 * they are, the same way a hidden menu language does.
 */
trait ValidatesDishOptions
{
    /**
     * @param  array<int, string>  $languages
     * @return array<string, mixed>
     */
    protected function dishOptionRules(array $languages): array
    {
        $restaurant = $this->user()?->restaurant;
        $limits = config('menu.dish_options');
        $name = $limits['name_max'];
        $price = ['nullable', 'numeric', 'min:0', 'max:'.$limits['price_max']];
        $rules = [];

        if ($restaurant?->showsVariants()) {
            $rules += [
                'variants' => ['sometimes', 'array', 'max:'.$limits['variants']],
                'variants.*' => ['array'],
                'variants.*.id' => ['nullable', 'integer'],
                'variants.*.name' => ['required', 'array'],
                ...MenuLanguages::rules('variants.*.name', $languages, $name, 'required'),
                'variants.*.options' => ['required', 'array', 'min:2', 'max:'.$limits['options']],
                'variants.*.options.*' => ['array'],
                'variants.*.options.*.id' => ['nullable', 'integer'],
                'variants.*.options.*.name' => ['required', 'array'],
                ...MenuLanguages::rules('variants.*.options.*.name', $languages, $name, 'required'),
                'variants.*.options.*.price' => $price,
            ];
        }

        if ($restaurant?->showsAddons()) {
            $rules += [
                'addons' => ['sometimes', 'array', 'max:'.$limits['addons']],
                'addons.*' => ['array'],
                'addons.*.id' => ['nullable', 'integer'],
                'addons.*.name' => ['required', 'array'],
                ...MenuLanguages::rules('addons.*.name', $languages, $name, 'required'),
                'addons.*.price' => $price,
            ];
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    protected function dishOptionMessages(): array
    {
        return [
            'variants.*.name.en.required' => __('Give every variant a name in English.'),
            'variants.*.options.required' => __('A variant needs at least 2 options.'),
            'variants.*.options.min' => __('A variant needs at least 2 options.'),
            'variants.*.options.*.name.en.required' => __('Give every option a name in English.'),
            'addons.*.name.en.required' => __('Give every add-on a name in English.'),
        ];
    }

    /**
     * A variant or add-on adds to the dish's price, so the dish needs one.
     *
     * @param  string|null  $currentPrice  the saved price, for an edit that does not send one
     */
    protected function priceForOptions(Validator $validator, ?string $currentPrice): void
    {
        $restaurant = $this->user()?->restaurant;

        if (! $restaurant instanceof Restaurant) {
            return;
        }

        $hasOptions = ($restaurant->showsVariants() && (array) $this->input('variants') !== [])
            || ($restaurant->showsAddons() && (array) $this->input('addons') !== []);
        $price = $this->exists('price') ? $this->input('price') : $currentPrice;

        if ($hasOptions && ($price === null || $price === '')) {
            $validator->errors()->add('price', __('Give the dish a price before adding variants or add-ons.'));
        }
    }
}
