<?php

namespace App\Enums;

/** How a guest who ordered in the menu gets their food. */
enum Fulfilment: string
{
    case Delivery = 'delivery';
    case Pickup = 'pickup';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
