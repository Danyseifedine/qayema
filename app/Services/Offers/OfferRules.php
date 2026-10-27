<?php

namespace App\Services\Offers;

use App\Enums\Feature;
use App\Enums\OfferPlacement;
use App\Models\Offer;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Collection;

/**
 * Which parts of offers a restaurant's package includes, so an admin can
 * build a package from parts: a place is in it while its count
 * (`offer_*_limit`, OfferPlacement::limit()) is above 0 or unlimited, and
 * schedules and discounts are switches of their own. What the package
 * leaves out is refused on save (StoreOfferRequest) and left off the menu
 * and the orders (LiveOffers); nothing saved is deleted.
 */
final class OfferRules
{
    /** What a place left out of the package says, by place. */
    public const PLACE_OFF = [
        'banner' => 'Banners are not on your package.',
        'popup' => 'Pop-ups are not on your package.',
        'ad' => 'Short ads are not on your package.',
    ];

    public const SCHEDULES_OFF = 'Schedules are not on your package.';

    public const DISCOUNTS_OFF = 'Discounts are not on your package.';

    public function __construct(private readonly Restaurant $restaurant) {}

    public static function for(Restaurant $restaurant): self
    {
        return new self($restaurant);
    }

    /** The place is on the package: its count is above 0, or unlimited. */
    public function allowsPlace(OfferPlacement $placement): bool
    {
        return $this->restaurant->limitOf($placement->limit()) !== 0;
    }

    public function allowsSchedules(): bool
    {
        return $this->restaurant->entitlements()->can(Feature::OfferSchedules);
    }

    public function allowsDiscounts(): bool
    {
        return $this->restaurant->entitlements()->can(Feature::OfferDiscounts);
    }

    /**
     * Why the offer stays off the menu while the package is as it is:
     * `schedules` (it has a schedule the package no longer runs) or
     * `places` (none of its places is on the package); null when it shows.
     */
    public function keepsOff(Offer $offer): ?string
    {
        return match (true) {
            $offer->hasSchedule() && ! $this->allowsSchedules() => 'schedules',
            array_filter($offer->placementList(), $this->allowsPlace(...)) === [] => 'places',
            default => null,
        };
    }

    /**
     * The offers as the menu and the orders may use them: the ones kept off
     * dropped, the rest shown only in the places the package has, and
     * without a discount the package lacks.
     *
     * @param  Collection<int, Offer>  $offers
     * @return Collection<int, Offer>
     */
    public function applyTo(Collection $offers): Collection
    {
        return $offers
            ->reject(fn (Offer $offer): bool => $this->keepsOff($offer) !== null)
            ->each(function (Offer $offer): void {
                $offer->showOnlyIn(array_values(array_filter($offer->placementList(), $this->allowsPlace(...))));

                if (! $this->allowsDiscounts()) {
                    $offer->withoutDiscount();
                }
            })
            ->values();
    }
}
