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

    private static function message(Restaurant $restaurant, Order $order): string
    {
        // The labels below come through __(), in the app locale the order
        // request set from the guest's menu language; the name follows it.
        $name = MenuLanguages::text($restaurant, 'name', app()->getLocale());
        $symbol = (string) config("currencies.{$restaurant->currency}.symbol", $restaurant->currency);

        $lines = [
            __('New order :reference', ['reference' => $order->reference]),
            $name,
        ];

        // Scanned at a table: the first thing staff need to know.
        if ($order->table_name !== null) {
            $lines[] = __('Table').': '.$order->table_name;
        }

        $lines[] = '';

        foreach ($order->items as $item) {
            $lines[] = $item->quantity.' × '.$item->name.'  '.$symbol.Price::format($item->line_total);

            if ($item->choices() !== []) {
                $lines[] = '    '.implode(', ', $item->choices());
            }
        }

        $lines[] = '';
        $lines[] = __('Total').': '.$symbol.Price::format($order->total);

        if ($order->note !== null) {
            $lines[] = '';
            $lines[] = __('Note').': '.$order->note;
        }

        return implode("\n", $lines);
    }
}
