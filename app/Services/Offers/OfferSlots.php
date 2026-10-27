<?php

namespace App\Services\Offers;

use App\Enums\OfferPlacement;
use App\Models\Offer;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/**
 * How many offers in each place a restaurant may have switched on at once
 * (the package's `offer_*_limit`, or the admin's grant for it), and how
 * many it has. A paused offer, or one past its end, takes no slot.
 */
final class OfferSlots
{
    /** What a full place says, by place. */
    private const FULL = [
        'banner' => 'Your package allows :limit banner offers switched on at a time. Pause or end one first.',
        'popup' => 'Your package allows :limit pop-up offers switched on at a time. Pause or end one first.',
        'ad' => 'Your package allows :limit short ad offers switched on at a time. Pause or end one first.',
    ];

    /**
     * Each place's slots: used, and the limit (null for unlimited).
     *
     * @param  \Illuminate\Support\Collection<int, Offer>|null  $offers  every offer of the restaurant, when already read (the list)
     * @return array<string, array{used: int, limit: int|null}>
     */
    public static function of(Restaurant $restaurant, ?\Illuminate\Support\Collection $offers = null): array
    {
        $taking = $offers === null ? self::taking($restaurant) : $offers->filter(fn (Offer $offer): bool => self::takesSlots($offer));
        $slots = [];

        foreach (OfferPlacement::cases() as $placement) {
            $slots[$placement->value] = [
                'used' => $taking->filter(fn (Offer $offer): bool => $offer->showsIn($placement))->count(),
                'limit' => $restaurant->limitOf($placement->limit()),
            ];
        }

        return $slots;
    }

    /** Takes a slot in each of its places: switched on and not past its end. */
    public static function takesSlots(Offer $offer): bool
    {
        return $offer->is_active && ($offer->ends_at === null || $offer->ends_at->isFuture());
    }

    /**
     * Refuses an offer about to take a slot in a place that is full, as a
     * validation error on `$field`. Call it under the restaurant's row lock,
     * with the offer as it is about to be saved.
     *
     * @throws ValidationException
     */
    public static function ensureRoomFor(Offer $offer, Restaurant $restaurant, string $field): void
    {
        if (! self::takesSlots($offer)) {
            return;
        }

        $others = self::taking($restaurant)->reject(fn (Offer $other): bool => $other->is($offer));

        foreach ($offer->placementList() as $placement) {
            $limit = $restaurant->limitOf($placement->limit());

            // 0: the place is not on the package, so the offer takes no slot
            // there (OfferRules keeps it off that place, and refuses saving it).
            if ($limit !== null && $limit > 0 && $others->filter(fn (Offer $other): bool => $other->showsIn($placement))->count() >= $limit) {
                throw ValidationException::withMessages([
                    $field => __(self::FULL[$placement->value], ['limit' => $limit]),
                ]);
            }
        }
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Offer> */
    private static function taking(Restaurant $restaurant): \Illuminate\Database\Eloquent\Collection
    {
        $now = CarbonImmutable::now();

        return $restaurant->offers()
            ->where('is_active', true)
            ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->get(['id', 'restaurant_id', 'placements', 'is_active', 'ends_at']);
    }
}
