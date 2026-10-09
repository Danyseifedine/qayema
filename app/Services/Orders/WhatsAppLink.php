<?php

namespace App\Services\Orders;

use App\Models\Order;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\PhoneNumber;
use App\Support\Price;

/**
 * The hand-off that actually reaches an owner.
 *
 * The product has no realtime and no push, so a stored order on its own would
 * sit unseen until someone opened the dashboard. Sending the guest to WhatsApp
 * with the order already written out puts it in front of the owner where they
 * already look.
 */
class WhatsAppLink
{
    /** Null when the restaurant has no usable number to send to. */
    public static function forOrder(Restaurant $restaurant, Order $order): ?string
    {
        $number = self::internationalNumber($restaurant);

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode(self::message($restaurant, $order));
    }

    /** The restaurant's phone as wa.me wants it (App\Support\PhoneNumber). */
    public static function internationalNumber(Restaurant $restaurant): ?string
    {
        return PhoneNumber::international($restaurant->country_code, $restaurant->phone);
    }

    /**
     * The order as the owner reads it, in WhatsApp's own bold (*...*), with
     * no emoji. Written in the menu's main language, the one the owner wrote
     * the menu in, whatever language the guest was reading: the lines were
     * named in it too (PublicOrderController). No reference: nobody looks an
     * order up on WhatsApp.
     *
     *     *New order · Olive*
     *     *Table 5*
     *
     *     *2 × Burger*   $16
     *        Size: Large
     *        + Extra cheese
     *
     *     *Total: $16*
     *
     *     Note: no onions
     */
    private static function message(Restaurant $restaurant, Order $order): string
    {
        $main = MenuLanguages::main($restaurant);
        $say = fn (string $key): string => __($key, [], $main);
        $symbol = (string) config("currencies.{$restaurant->currency}.symbol", $restaurant->currency);
        $money = fn (string $amount): string => $symbol.Price::format($amount);

        $name = MenuLanguages::reader($restaurant, $main)($restaurant, 'name');
        $lines = [self::bold($say('New order').' · '.$name)];

        // Scanned at a table: the first thing staff need to know.
        if ($order->table_name !== null) {
            $lines[] = self::bold(self::table($order->table_name, $say('Table')));
        }

        $lines[] = '';

        foreach ($order->items as $item) {
            $lines[] = self::bold($item->quantity.' × '.$item->name).'   '.$money((string) $item->line_total);

            foreach ($item->choices() as $choice) {
                $lines[] = '   '.$choice;
            }
        }

        $lines[] = '';
        $lines[] = self::bold($say('Total').': '.$money((string) $order->total));

        if ($order->note !== null) {
            $lines[] = '';
            $lines[] = $say('Note').': '.$order->note;
        }

        return implode("\n", $lines);
    }

    /** WhatsApp bold. An asterisk in a name would end it early, so it goes. */
    private static function bold(string $text): string
    {
        return '*'.trim(str_replace('*', '', $text)).'*';
    }

    /** "Table: Terrace", or just "Table 5" when the name already says so. */
    private static function table(string $name, string $word): string
    {
        return mb_stripos($name, $word) !== false ? $name : $word.': '.$name;
    }
}
