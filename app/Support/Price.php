<?php

namespace App\Support;

/**
 * A price as a guest reads it: a whole amount without decimals ("10,000"),
 * anything else with its two ("12.50"). The menu, the cart, the dish sheet,
 * the guest's tracking and the WhatsApp message all print prices this way;
 * public/js/menu-cart.js and menu-dish.js mirror it in `money()`.
 */
final class Price
{
    public static function format(mixed $amount): string
    {
        $value = round((float) $amount, 2);

        return number_format($value, $value == floor($value) ? 0 : 2);
    }
}
