<?php

namespace App\Http\Requests\Concerns;

use App\Enums\BuildShape;
use App\Models\Dish;
use App\Services\Menu\MenuLanguages;
use App\Support\MenuTextLimits;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * A dish's "build your own" (App\Models\DishBuild), as the dashboard's dish
 * form sends them: `builds: [{id?, name, shape, parts, category_id,
 * dish_ids?, choices: [{id?, dish_id?, name?, price?, part_prices?}]}]`. Validated only
 * while the restaurant has the feature; otherwise left out of `validated()`,
 * so saved builds stay as they are.
 *
 * A build with a category takes that category's dishes (only those of
 * `dish_ids` when it lists some; null or left out, every one), each
 * `choices` row naming one (`dish_id`) to set its part prices; a build without one lists
 * its own choices, at least two, each named and priced.
 */
trait ValidatesDishBuilds
{
    /**
     * @param  array<int, string>  $languages
     * @return array<string, mixed>
     */
    protected function dishBuildRules(array $languages): array
    {
        $restaurant = $this->user()?->restaurant;

        if (! $restaurant?->showsBuilds()) {
            return [];
        }

        $limits = config('menu.dish_builds');
        $money = ['nullable', 'numeric', 'min:0', 'max:'.config('menu.dish_options.price_max')];

        return [
            'builds' => ['sometimes', 'array', 'max:'.$limits['builds']],
            'builds.*' => ['array'],
            'builds.*.id' => ['nullable', 'integer'],
            'builds.*.name' => ['required', 'array'],
            ...MenuLanguages::rules('builds.*.name', $languages, MenuTextLimits::CHOICE_NAME, 'required'),
            'builds.*.shape' => ['required', Rule::enum(BuildShape::class)],
            'builds.*.parts' => ['required', 'array', 'min:1', 'max:'.$limits['parts']],
            'builds.*.parts.*' => ['integer', 'distinct', 'min:1', 'max:'.$limits['parts']],
            'builds.*.category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $restaurant->id)],
            'builds.*.dish_ids' => ['nullable', 'array', 'max:500'],
            'builds.*.dish_ids.*' => ['integer', 'distinct'],
            // A category may hold more dishes than an own list may (its cap is
            // checkBuilds()'s): as many as a menu reorders at once.
            'builds.*.choices' => ['present', 'array', 'max:500'],
            'builds.*.choices.*' => ['array'],
            'builds.*.choices.*.id' => ['nullable', 'integer'],
            'builds.*.choices.*.dish_id' => ['nullable', 'integer'],
            'builds.*.choices.*.name' => ['nullable', 'array'],
            ...MenuLanguages::rules('builds.*.choices.*.name', $languages, MenuTextLimits::CHOICE_NAME),
            'builds.*.choices.*.price' => $money,
            'builds.*.choices.*.part_prices' => ['nullable', 'array'],
            'builds.*.choices.*.part_prices.*' => $money,
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function dishBuildMessages(): array
    {
        return MenuLanguages::requiredMessages('builds.*.name', MenuLanguages::mainForOwner($this->user()), 'Give every build a name in your menu\'s main language (:language).');
    }

    /**
     * What one field cannot say: an own list has two choices or more, each
     * named in the main language and priced; a category's choices and the
     * dishes it ticks are its dishes, never the dish itself. The dishes are
     * read in one query.
     */
    protected function checkBuilds(Validator $validator, ?Dish $dish): void
    {
        $restaurant = $this->user()?->restaurant;
        $builds = $this->input('builds');

        if (! $restaurant?->showsBuilds() || ! is_array($builds) || $validator->errors()->isNotEmpty()) {
            return;
        }

        $main = MenuLanguages::mainForOwner($this->user());
        $limit = (int) config('menu.dish_builds.choices');
        $named = collect($builds)->flatMap(fn (mixed $build): array => is_array($build) && filled($build['category_id'] ?? null)
            ? [...array_filter(array_column((array) ($build['choices'] ?? []), 'dish_id')), ...(array) ($build['dish_ids'] ?? [])]
            : []);
        $categoryOf = $named->isEmpty() ? collect() : Dish::query()
            ->where('restaurant_id', $restaurant->id)
            ->whereIn('id', $named->map('intval')->all())
            ->pluck('category_id', 'id');

        foreach ($builds as $b => $build) {
            $choices = (array) ($build['choices'] ?? []);

            if (filled($build['category_id'] ?? null)) {
                $ofCategory = fn (mixed $id): bool => (int) $id !== $dish?->id && (int) $categoryOf->get((int) $id) === (int) $build['category_id'];

                foreach ($choices as $c => $choice) {
                    if (! $ofCategory($choice['dish_id'] ?? 0)) {
                        $validator->errors()->add("builds.{$b}.choices.{$c}.dish_id", __('Pick a dish of the build\'s category.'));
                    }
                }

                foreach ((array) ($build['dish_ids'] ?? []) as $d => $id) {
                    if (! $ofCategory($id)) {
                        $validator->errors()->add("builds.{$b}.dish_ids.{$d}", __('Pick a dish of the build\'s category.'));
                    }
                }

                continue;
            }

            if (count($choices) < 2 || count($choices) > $limit) {
                $validator->errors()->add("builds.{$b}.choices", __('A build needs 2 to :max choices.', ['max' => $limit]));
            }

            foreach ($choices as $c => $choice) {
                if (blank($choice['name'][$main] ?? null)) {
                    $validator->errors()->add("builds.{$b}.choices.{$c}.name.{$main}", __('Give every choice a name in your menu\'s main language (:language).', ['language' => MenuLanguages::nameOf($main)]));
                }

                if (($choice['price'] ?? null) === null || $choice['price'] === '') {
                    $validator->errors()->add("builds.{$b}.choices.{$c}.price", __('Give every choice a price.'));
                }
            }
        }
    }

    /**
     * Whether the dish ends up with a build to price it: the builds sent, or
     * the saved ones when an edit sends none.
     */
    protected function hasBuilds(bool $hasSavedBuilds): bool
    {
        $restaurant = $this->user()?->restaurant;

        return (bool) $restaurant?->showsBuilds()
            && ($this->exists('builds') ? (array) $this->input('builds') !== [] : $hasSavedBuilds);
    }
}
