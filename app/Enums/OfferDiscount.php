<?php

namespace App\Enums;

/**
 * What an offer takes off its dishes while it is live: nothing, a share of
 * the dish's price, an amount off it, or (`second`, a pair deal) a share off
 * every second dish of the offer a guest orders ("the second at 50% off",
 * 100 is free). The price is the dish's own plus the variants picked (a
 * Large); add-ons and builds keep theirs.
 */
enum OfferDiscount: string
{
    case None = 'none';
    case Percent = 'percent';
    case Amount = 'amount';
    case Second = 'second';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The dish's price after the discount, in cents, never below nothing.
     * Worked in whole cents and rounded half up, as menu-offers.js does on
     * the guest's phone, so the cart and the order agree to the cent.
     */
    public function apply(int $cents, float $value): int
    {
        return match ($this) {
            self::None => $cents,
            // A pair deal's second dish takes its share off as a percent does.
            self::Percent, self::Second => max(0, (int) round($cents * (100 - $value) / 100)),
            self::Amount => max(0, $cents - (int) round($value * 100)),
        };
    }

    /** A pair deal: only every second dish is cheaper, not each one. */
    public function isPair(): bool
    {
        return $this === self::Second;
    }
}
