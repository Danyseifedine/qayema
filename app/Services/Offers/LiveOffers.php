<?php

namespace App\Services\Offers;

use App\Enums\Feature;
use App\Enums\OfferDiscount;
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
        if (! $restaurant->entitlements()->can(Feature::Offers)) {
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
        if ($offerIds === [] || ! $restaurant->entitlements()->can(Feature::Offers)) {
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
     * For each of these dishes on a live discount at `$at`, the offer that
     * leaves it cheapest: when two offers cover a dish, the guest gets the
     * better one. One query whatever the cart holds.
     *
     * @param  array<int, int>  $dishIds
     * @param  array<int, string>  $prices  dish id => its own price ("12.50"), to compare the offers on
     * @param  array<int, string>  $rates  dish id => its rate, for a dish priced in the second currency
     * @return array<int, Offer> dish id => offer
     */
    public function bestFor(Restaurant $restaurant, array $dishIds, array $prices, CarbonImmutable $at, array $rates = []): array
    {
        $rules = OfferRules::for($restaurant);

        if ($dishIds === [] || ! $restaurant->entitlements()->can(Feature::Offers) || ! $rules->allowsDiscounts()) {
            return [];
        }

        $offers = $rules->applyTo($restaurant->offers()
            ->where('is_active', true)
            ->where('discount_type', '!=', OfferDiscount::None->value)
            ->where(fn (Builder $open) => $open->whereNull('ends_at')->orWhere('ends_at', '>', $at))
            ->where(fn (Builder $started) => $started->whereNull('starts_at')->orWhere('starts_at', '<=', $at))
            ->whereHas('dishes', fn (Builder $dishes) => $dishes->whereIn('dishes.id', $dishIds))
            ->with(['dishes' => fn ($dishes) => $dishes->select('dishes.id')->whereIn('dishes.id', $dishIds)])
            ->get());

        return $this->bestAmong($offers, $prices, $at, $restaurant->localTimezone(), $rates);
    }

    /**
     * The same choice among offers already in hand (the menu page's), so
     * drawing the discounted prices costs no query.
     *
     * @param  iterable<Offer>  $offers  each with its `dishes` loaded
     * @param  array<int, string>  $prices  dish id => its own price
     * @param  array<int, string>  $rates  dish id => its rate, for a dish priced in the second currency
     * @return array<int, Offer> dish id => offer
     */
    public function bestAmong(iterable $offers, array $prices, CarbonImmutable $at, string $timezone, array $rates = []): array
    {
        $best = [];

        foreach ($offers as $offer) {
            if (! $offer->hasDiscount() || ! $offer->isLiveAt($at, $timezone)) {
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
}
