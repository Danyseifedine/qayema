<?php

namespace App\Enums;

/**
 * How a guest who ordered in the menu gets their food: brought to them,
 * collected, or served at the table they ordered from (its QR code).
 *
 * Delivery and pickup are the restaurant's `order_types` (away()); dine-in
 * is a feature of its own (Feature::DineIn, Restaurant::takesDineIn()).
 */
enum Fulfilment: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';
    case DineIn = 'dine_in';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * The kinds that leave the restaurant, which the owner picks among for
     * ordering (Features → Orders).
     *
     * @return array<int, string>
     */
    public static function away(): array
    {
        return [self::Delivery->value, self::Pickup->value];
    }
}
