<?php

namespace App\Services\Orders;

use App\Enums\Fulfilment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\Ingredients;
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
    /**
     * Null when the restaurant has no usable number to send to. Written in
     * `$locale`, the language the guest had the menu in (one of its own,
     * PlaceOrderRequest::guestLocale()).
     */
    public static function forOrder(Restaurant $restaurant, Order $order, string $locale): ?string
    {
        $number = self::internationalNumber($restaurant);

        if ($number === null) {
            return null;
        }

        return 'https://wa.me/'.$number.'?text='.rawurlencode(self::message($restaurant, $order, $locale));
    }

    /** The restaurant's phone as wa.me wants it (App\Support\PhoneNumber). */
    public static function internationalNumber(Restaurant $restaurant): ?string
    {
        return PhoneNumber::international($restaurant->country_code, $restaurant->phone);
    }

    /**
     * The order as the staff read it, kept short and plain: WhatsApp's own
     * bold (*...*), no emoji, a blank line between the parts. Written in the
     * language the guest had the menu in, the lines' names too (OrderPlacer
     * names them in it). No reference: nobody looks an order up on WhatsApp.
     *
     *     *New order · Olive*
     *     *Delivery*           (or *Table 5* / *Pickup*)
     *
     *     Name: Rami           (what the guest was asked for and gave)
     *     Phone: +96170123456
     *
     *     *2 Burger*   $16
     *     • Large
     *     • Extra cheese
     *
     *     *1 Large: Half Margherita, Half Pepperoni*   $14
     *
     *     *Total: $30*
     *     $1 = L.L 89,000      (when a line was in the second currency)
     *
     *     Note: no onions
     */
    private static function message(Restaurant $restaurant, Order $order, string $locale): string
    {
        $say = fn (string $key): string => __($key, [], $locale);
        $symbol = fn (string $currency): string => (string) config("currencies.{$currency}.symbol", $currency);
        // A line in its own currency (one priced in the second); the total in the main one.
        $money = fn (string $amount, ?string $currency = null): string => Price::money($symbol($currency ?? $restaurant->currency), $amount);

        $name = MenuLanguages::reader($restaurant, $locale)($restaurant, 'name');
        $top = [self::bold($say('New order').' · '.$name)];

        // Scanned at a table: the first thing staff need to know.
        if ($order->table_name !== null) {
            $top[] = self::bold(self::table($order->table_name, $say('Table')));
        } elseif ($order->fulfilment === Fulfilment::Delivery || $order->fulfilment === Fulfilment::Pickup) {
            $top[] = self::bold($say($order->fulfilment === Fulfilment::Delivery ? 'Delivery' : 'Pickup'));
        }

        // What the restaurant asked the guest for (Restaurant::whatsappAsks()).
        $details = [];
        foreach ([
            'Name' => $order->guest_name,
            'Phone' => $order->guest_phone,
            'Address' => $order->address,
            // Stored to seven places; the link needs no trailing zeros.
            'Location' => $order->latitude !== null ? 'https://www.google.com/maps?q='.(float) $order->latitude.','.(float) $order->longitude : null,
        ] as $label => $value) {
            if ($value !== null && $value !== '') {
                $details[] = $say($label).': '.$value;
            }
        }

        $dishes = $order->items->map(fn (OrderItem $item): array => self::dish($item, $locale, $money((string) $item->line_total, $item->currency)))->all();

        $total = [self::bold($say('Total').': '.$money((string) $order->total))];
        $second = $order->items->firstWhere('currency', '!=', null)?->currency;
        if ($order->exchange_rate !== null && $second !== null) {
            // As the menu's cart writes it: "$1 = L.L 89,000".
            $total[] = $money('1', $second).' = '.$money(Price::toMain('1', (string) $order->exchange_rate));
        }

        $note = $order->note !== null ? [$say('Note').': '.$order->note] : [];

        $parts = array_filter([$top, $details, ...$dishes, $total, $note]);

        return implode("\n\n", array_map(fn (array $lines): string => implode("\n", $lines), $parts));
    }

    /**
     * One dish: "*2 Burger*   $16" (no sign between, nothing to misread),
     * then a "• " line for each choice, just what was picked ("Large",
     * "Extra cheese"). A built dish is named by what
     * was built instead of its own name ("*1 Large: Half Margherita, Half
     * Pepperoni*"); a second build, which no dish has yet, is a line below.
     *
     * @return list<string>
     */
    private static function dish(OrderItem $item, string $locale, string $price): array
    {
        $options = (array) $item->options;
        $builds = array_map(fn (array $build): string => self::built($build, $locale), $options['builds'] ?? []);
        $title = $builds === [] ? $item->name : array_shift($builds);

        return [
            self::bold($item->quantity.' '.$title).'   '.$price,
            ...array_map(fn (string $choice): string => '• '.$choice, [
                ...array_column($options['variants'] ?? [], 'choice'),
                ...$builds,
                ...array_column($options['addons'] ?? [], 'name'),
            ]),
        ];
    }

    /**
     * A build in plain words, its size first when it has one: "Large: Half
     * Margherita, Half Pepperoni". The same choice twice is added up, so two
     * quarters of one pizza are "Half Margherita" and a pizza of one choice
     * is just "Margherita"; scoops are each named, the same ones together
     * ("Vanilla, Vanilla, Chocolate").
     *
     * @param  array{parts?: array<int, array{choice: string, share: int}>, size?: string}  $build
     */
    private static function built(array $build, string $locale): string
    {
        $parts = array_values((array) ($build['parts'] ?? []));
        $share = (int) ($parts[0]['share'] ?? 1);
        $words = [];

        foreach (array_count_values(array_column($parts, 'choice')) as $choice => $count) {
            // A scoop twice is named twice: a count would run into the quantity.
            array_push($words, ...($share > 1
                ? [self::portion((string) $choice, $count, $share, $locale)]
                : array_fill(0, $count, (string) $choice)));
        }

        $text = Ingredients::join($words, $locale);

        return filled($build['size'] ?? null) ? $build['size'].': '.$text : $text;
    }

    /** `$count` parts of a dish split in `$share`, the share reduced: "Half Margherita", "Two thirds Margherita". */
    private static function portion(string $choice, int $count, int $share, string $locale): string
    {
        $common = self::divisor($count, $share);
        $fraction = intdiv($count, $common).'/'.intdiv($share, $common);
        $replace = ['name' => $choice];

        return match ($fraction) {
            '1/1' => $choice,
            '1/2' => __('Half :name', $replace, $locale),
            '1/3' => __('A third :name', $replace, $locale),
            '2/3' => __('Two thirds :name', $replace, $locale),
            '1/4' => __('A quarter :name', $replace, $locale),
            '3/4' => __('Three quarters :name', $replace, $locale),
            default => $fraction.' '.$choice,
        };
    }

    /** The greatest common divisor, to reduce a share. */
    private static function divisor(int $a, int $b): int
    {
        return $b === 0 ? $a : self::divisor($b, $a % $b);
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
