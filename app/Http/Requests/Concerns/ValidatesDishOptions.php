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
        $languages = MenuLanguages::forOwner($this->user());

        return [
            ...MenuLanguages::requiredMessages('variants.*.name', $languages, 'Give every variant a name in your menu\'s main language (:language).'),
            'variants.*.options.required' => __('A variant needs at least 2 options.'),
            'variants.*.options.min' => __('A variant needs at least 2 options.'),
            ...MenuLanguages::requiredMessages('variants.*.options.*.name', $languages, 'Give every option a name in your menu\'s main language (:language).'),
            ...MenuLanguages::requiredMessages('addons.*.name', $languages, 'Give every add-on a name in your menu\'s main language (:language).'),
        ];
    }

    /**
     * An add-on adds to the dish's price, so a dish with add-ons needs one,
     * unless it has variants: then a dish with no price of its own costs
     * what its options cost (a sandwich priced by size alone).
     *
     * @param  string|null  $currentPrice  the saved price, for an edit that does not send one
     * @param  bool  $hasSavedVariants  the dish's saved variants, for an edit that does not send them
     */
    protected function priceForOptions(Validator $validator, ?string $currentPrice, bool $hasSavedVariants = false): void
    {
        $restaurant = $this->user()?->restaurant;

        if (! $restaurant instanceof Restaurant) {
            return;
        }

        $hasVariants = $restaurant->showsVariants()
            && ($this->exists('variants') ? (array) $this->input('variants') !== [] : $hasSavedVariants);
        $hasAddons = $restaurant->showsAddons() && (array) $this->input('addons') !== [];
        $price = $this->exists('price') ? $this->input('price') : $currentPrice;

        $unpriced = $price === null || $price === '';

        if ($hasAddons && ! $hasVariants && $unpriced) {
            $validator->errors()->add('price', __('Give the dish a price before adding add-ons.'));
        }

        // Priced by its first variant, each of its options needs a price: an
        // empty one would be a free sandwich, not a forgotten one. The raw
        // input keeps the order the form sent, so the first is index 0.
        if ($unpriced && $hasVariants && $this->exists('variants')) {
            $variants = (array) $this->input('variants');
            $options = is_array($variants[0] ?? null) ? (array) ($variants[0]['options'] ?? []) : [];

            foreach ($options as $index => $option) {
                if (! is_array($option) || ($option['price'] ?? null) === null || $option['price'] === '') {
                    $validator->errors()->add("variants.0.options.{$index}.price", __('Give it a price, or give the dish one.'));
                }
            }
        }
    }
}
