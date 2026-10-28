<?php

namespace App\Http\Requests;

use App\Enums\OfferDiscount;
use App\Enums\OfferPlacement;
use App\Enums\OfferRepeat;
use App\Http\Requests\Concerns\SharedFieldRules;
use App\Models\Dish;
use App\Models\Offer;
use App\Services\Menu\MenuLanguages;
use App\Services\Offers\LiveOffers;
use App\Services\Offers\OfferRules;
use App\Services\Offers\OfferSchedule;
use App\Support\Ingredients;
use App\Support\MenuTextLimits;
use App\Support\Price;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * An offer as the dashboard's editor sends it, whole: its text, where it
 * shows, what a tap does, the discount and its dishes, and the schedule.
 * Dates arrive as the owner's local "2026-11-01T18:00", in the restaurant's
 * timezone. Editing sends the same (UpdateOfferRequest).
 *
 * `deal` is the offer's own dish (Offer::deal()): an object makes or edits
 * it, null deletes it, and left out it stays as it is. An offer with one has
 * no discount and no other dishes.
 */
class StoreOfferRequest extends FormRequest
{
    use SharedFieldRules;

    /** How the editor writes a date and time, in the restaurant's timezone. */
    public const LOCAL_FORMAT = 'Y-m-d\TH:i';

    /** @var Collection<int, Dish>|null */
    private ?Collection $chosenDishes = null;

    /** What an offer with a deal dish may not have. */
    public const DEAL_ALONE = 'An offer with a deal dish has no discount and no other dishes.';

    public function authorize(): bool
    {
        return true;
    }

    /** A deal dish's ingredients are tidied as a dish's are (TidiesIngredients). */
    protected function prepareForValidation(): void
    {
        $ingredients = $this->input('deal.ingredients');

        if (! $this->sendsDeal() || ! is_array($ingredients)) {
            return;
        }

        $this->merge(['deal' => [...(array) $this->input('deal'), 'ingredients' => collect($ingredients)
            ->map(fn (mixed $text, string|int $code): mixed => is_string($text) || $text === null
                ? (Ingredients::tidy($text, (string) $code) ?? '')
                : $text)
            ->all()]]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $languages = MenuLanguages::forOwner($this->user());

        return [
            // Optional for a picture alone, whose words are in the picture.
            'show_text' => ['nullable', 'boolean'],
            'title' => [$this->showsText() ? 'required' : 'nullable', 'array'],
            ...MenuLanguages::rules('title', $languages, MenuTextLimits::OFFER_TITLE, $this->showsText() ? 'required' : 'nullable'),
            'description' => ['nullable', 'array'],
            ...MenuLanguages::rules('description', $languages, MenuTextLimits::OFFER_DESCRIPTION),

            'placements' => ['required', 'array', 'min:1', 'max:'.count(OfferPlacement::cases())],
            'placements.*' => ['string', 'distinct', Rule::in(OfferPlacement::values())],
            'banner_shape' => ['nullable', 'string', Rule::in(Offer::BANNER_SHAPES)],
            'popup_shape' => ['nullable', 'string', Rule::in(Offer::POPUP_SHAPES)],
            'action' => ['required', 'string', Rule::in(Offer::ACTIONS)],
            'action_url' => ['nullable', 'required_if:action,link', 'url:https', 'max:500'],
            'show_again' => ['required', 'string', Rule::in(Offer::SHOW_AGAIN)],
            'delay_seconds' => ['required', 'integer', 'min:0', 'max:10'],
            // The ad's own wait: after the pop-up closes, or the menu opens.
            'ad_delay_seconds' => ['nullable', 'integer', 'min:0', 'max:30'],
            'ad_seconds' => ['required', 'integer', 'min:3', 'max:30'],

            'discount_type' => ['required', 'string', Rule::in(OfferDiscount::values())],
            'discount_value' => [
                'nullable',
                Rule::requiredIf(fn (): bool => $this->input('discount_type') !== OfferDiscount::None->value),
                'numeric',
                'gt:0',
                // An amount is held below the cheapest chosen dish (after()), so
                // a big currency like the pound has room. A pair deal may make
                // the second dish free (100).
                match ($this->input('discount_type')) {
                    OfferDiscount::Percent->value => 'max:90',
                    OfferDiscount::Second->value => 'max:100',
                    default => 'max:99999999',
                },
            ],
            'dish_ids' => [
                'nullable',
                'array',
                'max:'.Offer::MAX_DISHES,
                // A deal dish is its one dish, linked on save.
                Rule::requiredIf(fn (): bool => ! $this->hasDeal() && ($this->input('discount_type') !== OfferDiscount::None->value || $this->input('action') === 'dishes')),
            ],
            // Each one the restaurant's own: checked in after(), all in one query.
            'dish_ids.*' => ['integer', 'distinct'],

            'starts_at' => ['nullable', 'date_format:'.self::LOCAL_FORMAT],
            'ends_at' => ['nullable', 'date_format:'.self::LOCAL_FORMAT],
            'repeat' => ['required', 'string', Rule::in(OfferRepeat::values())],
            'weekdays' => ['nullable', 'array', 'max:7'],
            'weekdays.*' => ['string', 'distinct', Rule::in(OfferSchedule::DAYS)],
            'month_days' => ['nullable', 'array', 'max:31'],
            'month_days.*' => ['integer', 'distinct', 'min:1', 'max:31'],
            'time_windows' => ['nullable', 'array', 'max:'.OfferSchedule::MAX_WINDOWS],
            'time_windows.*.from' => ['required', 'date_format:H:i'],
            'time_windows.*.to' => ['required', 'date_format:H:i'],
            // On the menu all the time, its deal working only in its hours.
            'shows_outside_hours' => ['nullable', 'boolean'],

            'is_active' => ['nullable', 'boolean'],
            'image_key' => $this->tempImageKeyRules(),
            'delete_image' => ['nullable', 'boolean'],

            'deal' => ['sometimes', 'nullable', 'array'],
            ...($this->sendsDeal() ? $this->dealRules($languages) : []),
        ];
    }

    /**
     * The deal dish, checked as a dish is (StoreDishRequest): a name in the
     * main language, a price above nothing in one of the menu's currencies,
     * one of the owner's categories, and its photo as a temp upload.
     *
     * @param  array<int, string>  $languages
     * @return array<string, mixed>
     */
    private function dealRules(array $languages): array
    {
        return [
            'deal.name' => ['required', 'array'],
            ...MenuLanguages::rules('deal.name', $languages, MenuTextLimits::DISH_NAME, 'required'),
            'deal.ingredients' => ['nullable', 'array'],
            ...MenuLanguages::rules('deal.ingredients', $languages, MenuTextLimits::INGREDIENTS),
            'deal.price' => ['required', 'numeric', 'gt:0', 'max:99999999.99'],
            'deal.currency' => $this->dishCurrencyRules(),
            'deal.category_id' => ['required', 'integer', Rule::exists('categories', 'id')->where('restaurant_id', $this->user()?->restaurant->id ?? 0)],
            'deal.image_key' => $this->tempImageKeyRules(),
            'deal.use_offer_image' => ['nullable', 'boolean'],
        ];
    }

    /** It sends a deal dish to make or edit (an object; null deletes it). */
    public function sendsDeal(): bool
    {
        return is_array($this->input('deal'));
    }

    /**
     * It will have a deal dish once saved: one sent now (UpdateOfferRequest
     * adds the one it has and keeps).
     */
    protected function hasDeal(): bool
    {
        return $this->sendsDeal();
    }

    /** It asks for the deal dish to go (`deal: null`). */
    public function dropsDeal(): bool
    {
        return $this->exists('deal') && $this->input('deal') === null;
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            ...MenuLanguages::requiredMessages('title', MenuLanguages::mainForOwner($this->user()), 'An offer needs a title in your menu\'s main language (:language).'),
            'image_key.required' => 'An offer without text needs a picture.',
            'placements.required' => 'Choose where the offer shows.',
            'action_url.required_if' => 'Add the link a tap opens.',
            'action_url.url' => 'The link must start with https://.',
            'discount_value.required' => 'Enter how much the discount takes off.',
            'discount_value.max' => 'A discount can take off at most :max%.',
            'dish_ids.required' => 'Choose the dishes this offer is for.',
            ...MenuLanguages::requiredMessages('deal.name', MenuLanguages::mainForOwner($this->user()), 'A dish name is required in your menu\'s main language (:language).'),
            'deal.price.required' => __('Give the deal dish a price.'),
            'deal.price.gt' => __('Give the deal dish a price above 0.'),
            'deal.category_id.required' => __('Choose the category the deal dish shows in.'),
            'deal.category_id.exists' => __('Choose one of your categories.'),
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            // A deal dish is the offer's one dish, at its own price.
            // Its own deal sent back among its dishes (as OfferResource lists it) is no other dish.
            $others = array_diff(array_map('intval', array_filter((array) $this->input('dish_ids', []), 'is_numeric')), $this->ownDealIds());
            if ($this->hasDeal() && ($this->input('discount_type') !== OfferDiscount::None->value || $others !== [])) {
                $validator->errors()->add('deal', __(self::DEAL_ALONE));
            }
        }, function (Validator $validator): void {
            $owned = $this->chosenDishes()->modelKeys();
            foreach ((array) $this->input('dish_ids', []) as $index => $id) {
                $key = "dish_ids.{$index}";
                if (! $validator->errors()->has($key) && ! in_array((int) $id, $owned, true)) {
                    $validator->errors()->add($key, __('validation.exists', ['attribute' => $validator->getDisplayableAttribute($key)]));
                }
            }
        }, function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            if (! $this->showsText() && ! $this->hasPicture()) {
                $validator->errors()->add('image_key', 'An offer without text needs a picture.');
            }

            // Only the parts of offers the package has (OfferRules).
            $restaurant = $this->user()?->restaurant;
            if ($restaurant !== null) {
                $rules = OfferRules::for($restaurant);
                foreach ((array) $this->input('placements', []) as $place) {
                    $placement = OfferPlacement::from((string) $place);
                    if (! $rules->allowsPlace($placement)) {
                        $validator->errors()->add('placements', __(OfferRules::PLACE_OFF[$placement->value]));
                    }
                }
                if ($this->sendsSchedule() && ! $rules->allowsSchedules()) {
                    $validator->errors()->add('repeat', __(OfferRules::SCHEDULES_OFF));
                }
                if ($this->input('discount_type') !== OfferDiscount::None->value && ! $rules->allowsDiscounts()) {
                    $validator->errors()->add('discount_type', __(OfferRules::DISCOUNTS_OFF));
                }
            }

            if ($this->input('discount_type') === OfferDiscount::Amount->value && ($cheapest = $this->cheapestDish()) !== null
                && bccomp((string) $this->input('discount_value'), $cheapest[1], 2) >= 0) {
                $currency = (string) $this->user()?->restaurant?->currency;
                $validator->errors()->add('discount_value', __(':dish costs :price: a discount must take off less than that.', [
                    'dish' => $cheapest[0]->getTranslation('name', MenuLanguages::mainForOwner($this->user()), true),
                    'price' => Price::money((string) config("currencies.{$currency}.symbol", $currency), $cheapest[1]),
                ]));
            }

            foreach (OfferSchedule::problems(
                $this->localDate('starts_at'),
                $this->localDate('ends_at'),
                OfferRepeat::from((string) $this->input('repeat')),
                $this->input('weekdays'),
                $this->input('month_days'),
                $this->input('time_windows') ?: null,
            ) as $field => $message) {
                $validator->errors()->add($field, $message);
            }
        }];
    }

    /** It asks for a schedule rather than always (Offer::hasSchedule(), as sent). */
    private function sendsSchedule(): bool
    {
        return $this->filled('starts_at')
            || $this->filled('ends_at')
            || $this->input('repeat') !== OfferRepeat::Always->value
            || ! empty($this->input('time_windows'));
    }

    /**
     * The chosen dish that costs least before add-ons (its own price and
     * cheapest variants, LiveOffers::referencePrice(), as the discount is
     * judged), with that price in the main currency (an amount off is in
     * it); a dish neither prices is left out.
     *
     * @return array{0: Dish, 1: string}|null
     */
    private function cheapestDish(): ?array
    {
        $restaurant = $this->user()?->restaurant;
        $variantsOn = (bool) $restaurant?->showsVariants();

        return $this->chosenDishes()
            ->map(function (Dish $dish) use ($restaurant, $variantsOn): ?array {
                $price = LiveOffers::referencePrice($dish, $variantsOn);
                $rate = $restaurant?->rateOf($dish);

                return $price === null ? null : [$dish, $rate === null ? $price : Price::toMain($price, $rate)];
            })
            ->filter()
            ->sortBy(fn (array $priced): float => (float) $priced[1])
            ->first();
    }

    /** The offer being edited (UpdateOfferRequest); none for a new one. */
    protected function offerId(): ?int
    {
        return null;
    }

    /**
     * The chosen dishes that are this offer's own deal dish.
     *
     * @return array<int, int>
     */
    private function ownDealIds(): array
    {
        $offerId = $this->offerId();

        return $offerId === null ? [] : $this->chosenDishes()->where('offer_id', $offerId)->modelKeys();
    }

    /**
     * The chosen dishes that are the restaurant's own, read once for every
     * check. Another offer's deal dish is not one to choose: it is that
     * offer's alone, at its own price.
     *
     * @return Collection<int, Dish>
     */
    private function chosenDishes(): Collection
    {
        $ids = array_values(array_filter((array) $this->input('dish_ids', []), fn (mixed $id): bool => is_int($id) || ctype_digit((string) $id)));

        return $this->chosenDishes ??= $ids === []
            ? new Collection
            : Dish::query()
                ->where('restaurant_id', $this->user()?->restaurant?->id)
                ->where(fn ($dishes) => $dishes->whereNull('offer_id')->when($this->offerId(), fn ($own, int $id) => $own->orWhere('offer_id', $id)))
                ->whereIn('id', array_map('intval', $ids))
                ->with('variants.options')
                ->get(['id', 'price', 'currency', 'name', 'offer_id']);
    }

    /** With a title and text (the default), or a picture alone. */
    public function showsText(): bool
    {
        return $this->boolean('show_text', true);
    }

    /**
     * A picture it will have once saved: one sent now, or the one it has and
     * keeps (UpdateOfferRequest reads the offer being edited).
     */
    protected function hasPicture(): bool
    {
        return $this->filled('image_key');
    }

    /** A local date the editor sent, as the instant it is in the restaurant's timezone. */
    public function localDate(string $field): ?CarbonImmutable
    {
        $value = $this->input($field);

        if (! is_string($value) || $value === '') {
            return null;
        }

        $timezone = $this->user()?->restaurant?->localTimezone() ?? config('app.timezone');

        return CarbonImmutable::createFromFormat(self::LOCAL_FORMAT, $value, $timezone)?->utc();
    }
}
