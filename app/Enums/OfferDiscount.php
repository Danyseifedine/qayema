<?php

namespace App\Enums;

/**
 * What an offer takes off its dishes' own price while it is live: nothing,
 * a share of it, or an amount. Variants and add-ons keep their price.
 */
enum OfferDiscount: string
{
    case None = 'none';
    case Percent = 'percent';
    case Amount = 'amount';

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
            self::Percent => max(0, (int) round($cents * (100 - $value) / 100)),
            self::Amount => max(0, $cents - (int) round($value * 100)),
        };
    }
}
