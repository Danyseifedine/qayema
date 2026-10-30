<?php

namespace App\Services\Offers;

use App\Enums\OfferDiscount;
use App\Models\Dish;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
use App\Models\Offer;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * A restaurant's offers as guests meet them: the ones the menu page carries
 * (switched on and not past their end; the phone works out which are live
 * now), and the discount the server charges on a dish at a given moment.
 * Nothing at all while the package has no `offers`, and only the parts of
 * offers it has (OfferRules). An offer's deal dish is sold only while the
 * offer is live (liveAmong()).
 */
class LiveOffers
{
    /**
     * Switched on, not past their end, in the owner's order, with what the
     * page draws (picture, dishes). An owner's preview of one offer adds it
     * even when paused or ended.
     *
     * @return Collection<int, Offer>
     */
    public function forMenu(Restaurant $restaurant, ?int $previewing = null): Collection
    {
        if (! $restaurant->showsOffers()) {
            return new Collection;
        }

        return OfferRules::for($restaurant)->applyTo($restaurant->offers()
            ->where(fn (Builder $shown) => $shown
                ->where(fn (Builder $running) => $running
                    ->where('is_active', true)
                    ->where(fn (Builder $open) => $open->whereNull('ends_at')->orWhere('ends_at', '>', now())))
                ->when($previewing !== null, fn (Builder $query) => $query->orWhere('offers.id', $previewing)))
            ->with(['media', 'dishes:id'])
            ->get());
    }

    /**
     * The ones of these offers live at `$at` as guests meet them: the
     * menu's offers (forMenu(): the package has offers, switched on, not
     * past their end, kept on by OfferRules) that are in their time now.
     * What decides whether a deal dish can be ordered; one query, and none
     * without an offer to ask about.
     *
     * @param  array<int, int>  $offerIds
     * @return array<int, int> the live offers' ids
     */
    public function liveAmong(Restaurant $restaurant, array $offerIds, CarbonImmutable $at): array
    {
        if ($offerIds === [] || ! $restaurant->showsOffers()) {
            return [];
        }

        $timezone = $restaurant->localTimezone();

        return OfferRules::for($restaurant)->applyTo($restaurant->offers()->whereIn('offers.id', $offerIds)->where('is_active', true)->get())
            ->filter(fn (Offer $offer): bool => $offer->isLiveAt($at, $timezone))
            ->map(fn (Offer $offer): int => $offer->id)
            ->values()
            ->all();
    }

    /**
     * The deals on these dishes at `$at`, from one query whatever the cart
     * holds: the best straight discount per dish (bestAmong(): when two
     * offers cover a dish, the guest gets the better one) and the pair deal
     * per dish (pairsAmong()).
     *
     * @param  array<int, int>  $dishIds
     * @param  array<int, string>  $prices  dish id => its reference price ("12.50", referencePrice()), to compare the offers on
     * @param  array<int, string>  $rates  dish id => its rate, for a dish priced in the second currency
     * @return array{0: array<int, Offer>, 1: array<int, Offer>} straight deals, pair deals, by dish id
     */
    public function dealsFor(Restaurant $restaurant, array $dishIds, array $prices, CarbonImmutable $at, array $rates = []): array
    {
        $rules = OfferRules::for($restaurant);

        if ($dishIds === [] || ! $restaurant->showsOffers() || ! $rules->allowsDiscounts()) {
            return [[], []];
        }

        $offers = $rules->applyTo($restaurant->offers()
            ->where('is_active', true)
            ->where('discount_type', '!=', OfferDiscount::None->value)
            ->where(fn (Builder $open) => $open->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->where(fn (Builder $started) => $started->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->whereHas('dishes', fn (Builder $dishes) => $dishes->whereIn('dishes.id', $dishIds))
            ->with(['dishes' => fn ($dishes) => $dishes->select('dishes.id')->whereIn('dishes.id', $dishIds)])
            ->get());
        $timezone = $restaurant->localTimezone();

        return [$this->bestAmong($offers, $prices, $at, $timezone, $rates), $this->pairsAmong($offers, $at, $timezone)];
    }

    /**
     * The same choice among offers already in hand (the menu page's), so
     * drawing the discounted prices costs no query. Straight discounts only:
     * a pair deal makes no single dish cheaper.
     *
     * @param  iterable<Offer>  $offers  each with its `dishes` loaded
     * @param  array<int, string>  $prices  dish id => its reference price
     * @param  array<int, string>  $rates  dish id => its rate, for a dish priced in the second currency
     * @return array<int, Offer> dish id => offer
     */
    public function bestAmong(iterable $offers, array $prices, CarbonImmutable $at, string $timezone, array $rates = []): array
    {
        $best = [];

        foreach ($offers as $offer) {
            if (! $offer->hasStraightDiscount() || ! $offer->isLiveAt($at, $timezone)) {
                continue;
            }

            foreach ($offer->dishes as $dish) {
                $price = $prices[$dish->id] ?? null;
                $rate = $rates[$dish->id] ?? null;

                // A discount that would take the whole price does not apply:
                // no dish is ever free (twinned by deals() in menu-offers.js).
                if ($price === null || bccomp($offer->discounted($price, $rate), '0', 2) <= 0) {
                    continue;
                }

                $current = $best[$dish->id] ?? null;

                if ($current === null || (float) $offer->discounted($price, $rate) < (float) $current->discounted($price, $rate)) {
                    $best[$dish->id] = $offer;
                }
            }
        }

        return $best;
    }

    /**
     * Each dish on a live pair deal at `$at` ("the second at 50% off"), the
     * first such offer in the owner's order when two name it. Whether a
     * dish on a straight discount takes it is the caller's to decide (the
     * straight one wins).
     *
     * @param  iterable<Offer>  $offers  each with its `dishes` loaded, in the owner's order
     * @return array<int, Offer> dish id => offer
     */
    public function pairsAmong(iterable $offers, CarbonImmutable $at, string $timezone): array
    {
        $pairs = [];

        foreach ($offers as $offer) {
            if (! $offer->hasPairDeal() || ! $offer->isLiveAt($at, $timezone)) {
                continue;
            }

            foreach ($offer->dishes as $dish) {
                $pairs[$dish->id] ??= $offer;
            }
        }

        return $pairs;
    }

    /**
     * What a dish costs at least before add-ons, to compare discounts on:
     * its own price (none: 0), while variants show the cheapest option of
     * each variant a guest must pick (two options or more), and the least
     * its builds add (`$builds`, MenuDishOptions::buildsFloor(), while builds
     * show). Null when none of them prices it. Needs `variants.options`
     * loaded when they show.
     */
    public static function referencePrice(Dish $dish, bool $variantsOn, string $builds = '0.00'): ?string
    {
        $price = $dish->price === null ? null : (string) $dish->price;
        $cheapest = $variantsOn
            ? $dish->variants
                ->filter(fn (DishVariant $variant): bool => $variant->options->count() >= 2)
                ->map(fn (DishVariant $variant): string => (string) $variant->options->min(fn (DishVariantOption $option): float => (float) $option->price))
            : collect();
        $built = bccomp($builds, '0', 2) > 0;

        if ($price === null && $cheapest->isEmpty() && ! $built) {
            return null;
        }

        $sum = $cheapest->reduce(fn (string $sum, string $option): string => bcadd($sum, number_format((float) $option, 2, '.', ''), 2), $price ?? '0.00');

        return $built ? bcadd($sum, $builds, 2) : $sum;
    }
}
