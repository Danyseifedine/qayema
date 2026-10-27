<?php

namespace App\Services\Menu;

use App\Enums\BuildShape;

/**
 * What a "build your own" adds to its dish, in whole cents so the menu's
 * twin (public/js/menu-dish.js, buildPrice()) comes to the same cent.
 *
 * - `split`, one item in N parts: a part pays the price its choice sets for
 *   that size (its `part_prices`, a half at $7), else its share of the
 *   whole. The shares are added up first and rounded once, half up, so half
 *   of $12 and half of $16 is $14, never a cent off.
 * - `pieces`, N whole items: each part is its choice's whole price.
 */
final class BuildPrice
{
    /**
     * @param  list<array{price: string, part_prices?: array<array-key, mixed>|null}>  $parts  each part's choice
     */
    public static function of(BuildShape $shape, array $parts): string
    {
        $count = count($parts);

        if ($count === 0) {
            return '0.00';
        }

        if ($shape === BuildShape::Pieces) {
            return self::money(array_sum(array_map(fn (array $part): int => self::cents($part['price']), $parts)));
        }

        $shares = 0;
        $set = 0;

        foreach ($parts as $part) {
            $own = self::setPrice($part['part_prices'] ?? null, $count);

            if ($own === null) {
                $shares += self::cents($part['price']);
            } else {
                $set += self::cents($own);
            }
        }

        // Half up: a share of $12.25 cut in two is $6.13.
        return self::money(intdiv(2 * $shares + $count, 2 * $count) + $set);
    }

    /**
     * The least the build can add: the cheapest choice in every part, for
     * whichever allowed count comes cheapest. What the dish card's "from"
     * price counts.
     *
     * @param  list<int>  $counts  the part counts allowed
     * @param  list<array{price: string, part_prices?: array<array-key, mixed>|null}>  $choices
     */
    public static function cheapest(BuildShape $shape, array $counts, array $choices): string
    {
        $lowest = null;

        foreach ($counts as $count) {
            foreach ($choices as $choice) {
                $price = self::of($shape, array_fill(0, $count, $choice));
                $lowest = $lowest === null || bccomp($price, $lowest, 2) < 0 ? $price : $lowest;
            }
        }

        return $lowest ?? '0.00';
    }

    /**
     * The price a choice sets for one part of $count, null for its share.
     * Only a split build has part sizes; a whole (1) is the choice's price.
     *
     * @param  array<array-key, mixed>|null  $partPrices
     */
    public static function setPrice(?array $partPrices, int $count): ?string
    {
        $price = $partPrices[(string) $count] ?? null;

        return $count > 1 && is_numeric($price) ? number_format((float) $price, 2, '.', '') : null;
    }

    private static function cents(string $price): int
    {
        return (int) bcmul($price, '100', 0);
    }

    private static function money(int $cents): string
    {
        return bcdiv((string) $cents, '100', 2);
    }
}
