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

    /**
     * A currency's symbol as it goes before an amount: a sign stays on the
     * number ("$5"), letters get a small space ("L.L 89,000"). The menu's
     * scripts do the same in money().
     */
    public static function symbol(string $symbol): string
    {
        return mb_strlen($symbol) > 1 ? $symbol.' ' : $symbol;
    }

    /** An amount with its symbol before it ("$5", "L.L 89,000"). */
    public static function money(string $symbol, mixed $amount): string
    {
        return self::symbol($symbol).self::format($amount);
    }

    /**
     * An amount in a restaurant's second currency as its main one, at the
     * rate (how many of the main one a unit of the second is worth), to the
     * cent, half up. The cart (menu-cart.js `toMain`) and the dashboard work
     * a total out the same way, line by line.
     */
    public static function toMain(string $amount, string $rate): string
    {
        return self::cents(bcmul($amount, $rate, 8));
    }

    /** An amount in the main currency as the second one: an offer's amount off a dish priced in it. */
    public static function fromMain(string $amount, string $rate): string
    {
        return self::cents(bcdiv($amount, $rate, 8));
    }

    /** A rate as the owner wrote it: no trailing zeros ("89000", "0.000011236"). */
    public static function rate(string $rate): string
    {
        return str_contains($rate, '.') ? rtrim(rtrim($rate, '0'), '.') : $rate;
    }

    /** To two places, half up (amounts here are never negative). */
    private static function cents(string $amount): string
    {
        return bcadd($amount, '0.005', 2);
    }
}
