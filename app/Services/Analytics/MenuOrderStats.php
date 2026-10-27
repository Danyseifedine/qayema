<?php

namespace App\Services\Analytics;

use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Dish;
use App\Models\Order;
use App\Models\OrderItem;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

/**
 * The orders side of the analytics (MenuStats): the current channel's
 * counts, and what the orders placed in the menu came to.
 *
 * Every number is added up in SQL, so "All time" on a busy restaurant reads a
 * few grouped rows rather than every order; only the wait of each order the
 * restaurant took on is read (two columns), for its median.
 */
class MenuOrderStats
{
    private const TOP = 5;

    public function __construct(private readonly StatsRange $range) {}

    /**
     * The orders placed in the menu, which are real orders with a total, a
     * status and lines, unlike a WhatsApp tap: what they came to, how they
     * ended, how fast the restaurant took them on, when guests order and
     * what they order most.
     *
     * Money counts only the orders in the restaurant's current currency, so
     * a switch of currency never adds dollars to pounds; every other number
     * counts them all. Cancelled orders are left out of the money, the
     * guests and the times, and counted on their own in `statuses`.
     *
     * @return array<string, mixed>
     */
    public function menuOrders(): array
    {
        $currency = (string) $this->range->restaurant->currency;
        $statuses = array_fill_keys(array_column(OrderStatus::cases(), 'value'), 0);
        $fulfilment = [];
        $firstOf = [];
        $cents = 0;
        $paid = 0;

        $groups = $this->menuOrdersInRange($this->range->from, null)
            ->toBase()
            ->selectRaw(
                'status, fulfilment, count(*) as orders, min(placed_at) as first_placed,
                sum(case when currency = ? then round(total * 100) else 0 end) as cents,
                sum(case when currency = ? then 1 else 0 end) as paid',
                [$currency, $currency],
            )
            ->groupBy('status', 'fulfilment')
            ->get();

        foreach ($groups as $group) {
            $statuses[$group->status] += (int) $group->orders;

            if ($group->status === OrderStatus::Cancelled->value) {
                continue;
            }

            if ($group->fulfilment !== null) {
                $fulfilment[$group->fulfilment] = ($fulfilment[$group->fulfilment] ?? 0) + (int) $group->orders;
                $firstOf[$group->fulfilment] = min($firstOf[$group->fulfilment] ?? $group->first_placed, $group->first_placed);
            }

            $cents += (int) $group->cents;
            $paid += (int) $group->paid;
        }

        // Most first; a tie in the order guests first chose them.
        uksort($fulfilment, fn (string $a, string $b): int => [$fulfilment[$b], $firstOf[$a]] <=> [$fulfilment[$a], $firstOf[$b]]);
        [$hours, $weekdays] = $this->whenPlaced();
        $guests = $this->guests();

        return [
            'currency' => $currency,
            'sales' => $this->money($cents),
            'average' => $paid > 0 ? $this->money(intdiv($cents + intdiv($paid, 2), $paid)) : null,
            // Null for "All time", which has no period before it.
            'previous_sales' => $this->range->from === null ? null : $this->previousSales($currency),
            'statuses' => $statuses,
            'fulfilment' => array_map(
                fn (string $key, int $count): array => ['key' => $key, 'count' => $count],
                array_keys($fulfilment),
                $fulfilment,
            ),
            'guests' => $guests['guests'],
            'returning_guests' => $guests['returning'],
            'minutes_to_accept' => $this->median($this->minutesToAccept()),
            'hours' => $hours,
            'weekdays' => $weekdays,
            'top_ordered' => $this->topDishesOrdered($currency),
        ];
    }

    /**
     * The current channel's orders from `$from`: those not cancelled, and
     * those done, in one pass.
     *
     * @return array{kept: int, done: int}
     */
    public function totals(?CarbonImmutable $from): array
    {
        $totals = $this->ordersInRange($from, null)
            ->where('channel', $this->channel())
            ->toBase()
            ->selectRaw(
                'sum(case when status != ? then 1 else 0 end) as kept, sum(case when status = ? then 1 else 0 end) as done',
                [OrderStatus::Cancelled->value, OrderStatus::Done->value],
            )
            ->first();

        return ['kept' => (int) ($totals->kept ?? 0), 'done' => (int) ($totals->done ?? 0)];
    }

    /** Orders of the current channel, cancelled ones left out. */
    public function count(?CarbonImmutable $from, ?CarbonImmutable $until): int
    {
        return $this->ordersInRange($from, $until)
            ->where('channel', $this->channel())
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->count();
    }

    /**
     * The orders not cancelled by local hour of day (0 to 23) and weekday
     * (Monday first): counted per quarter of an hour in UTC, then moved into
     * the restaurant's timezone. Every offset is whole quarters, so a quarter
     * lands in one local hour, exactly.
     *
     * @return array{0: array<int, int>, 1: array<int, int>}
     */
    private function whenPlaced(): array
    {
        $hours = array_fill(0, 24, 0);
        $weekdays = array_fill(0, 7, 0);
        $utc = new DateTimeZone('UTC');
        $local = new DateTimeZone($this->range->timezone);

        $quarters = $this->keptMenuOrders()
            ->toBase()
            ->selectRaw(
                "SUBSTR(placed_at, 1, 13) as bucket_hour,
                CASE WHEN SUBSTR(placed_at, 15, 2) < '15' THEN '00' WHEN SUBSTR(placed_at, 15, 2) < '30' THEN '15' WHEN SUBSTR(placed_at, 15, 2) < '45' THEN '30' ELSE '45' END as bucket_quarter,
                COUNT(*) as orders",
            )
            ->groupBy('bucket_hour', 'bucket_quarter')
            ->get();

        foreach ($quarters as $quarter) {
            [$hour, $weekday] = explode(' ', (new DateTimeImmutable("{$quarter->bucket_hour}:{$quarter->bucket_quarter}:00", $utc))->setTimezone($local)->format('G N'));
            $hours[(int) $hour] += (int) $quarter->orders;
            $weekdays[(int) $weekday - 1] += (int) $quarter->orders;
        }

        return [$hours, $weekdays];
    }

    /**
     * Guests who left a phone, and those of them who ordered more than once.
     *
     * @return array{guests: int, returning: int}
     */
    private function guests(): array
    {
        $phones = $this->keptMenuOrders()
            ->whereNotNull('guest_phone')
            ->where('guest_phone', '!=', '')
            ->toBase()
            ->selectRaw('count(*) as orders')
            ->groupBy('guest_phone');

        $counts = $phones->newQuery()
            ->fromSub($phones, 'phones')
            ->selectRaw('count(*) as guests, sum(case when orders > 1 then 1 else 0 end) as came_back')
            ->first();

        return ['guests' => (int) ($counts->guests ?? 0), 'returning' => (int) ($counts->came_back ?? 0)];
    }

    /**
     * Minutes from placing to being taken on, for each order taken on:
     * accepted, or moved straight to ready or done. A restaurant that skips
     * "accepted" still answered the order.
     *
     * @return array<int, int>
     */
    private function minutesToAccept(): array
    {
        $answered = 'coalesce(accepted_at, ready_at, case when status = ? then closed_at end)';

        return $this->keptMenuOrders()
            ->toBase()
            ->selectRaw("placed_at, {$answered} as answered_at", [OrderStatus::Done->value])
            ->whereRaw("{$answered} is not null", [OrderStatus::Done->value])
            ->get()
            ->map(fn (object $order): int => max(0, (int) round(abs(strtotime($order->answered_at.' UTC') - strtotime($order->placed_at.' UTC')) / 60)))
            ->all();
    }

    /** What the menu's orders came to in the period just before this one. */
    private function previousSales(string $currency): float
    {
        [$start, $end] = $this->range->previousPeriod();

        $cents = $this->menuOrdersInRange($start, $end)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->where('currency', $currency)
            ->toBase()
            ->sum(new Expression('round(total * 100)'));

        return $this->money((int) $cents);
    }

    /**
     * The dishes guests ordered most, by how many they ordered, with what
     * they came to. Lines name the dish as it was ordered, in the guest's
     * language; the list names it as the owner reads it today, and leaves
     * out a dish deleted since, like the dishes most added.
     *
     * @return array<int, array{name: string, quantity: int, sales: float}>
     */
    private function topDishesOrdered(string $currency): array
    {
        $orders = fn () => $this->keptMenuOrders()->select('id')->getQuery();

        $rows = OrderItem::query()
            ->whereIn('order_id', $orders())
            ->whereNotNull('dish_id')
            ->selectRaw('dish_id, SUM(quantity) as quantity')
            ->groupBy('dish_id')
            ->orderByDesc('quantity')
            ->orderBy('dish_id')
            ->limit(self::TOP)
            ->get();

        $names = $this->range->names(Dish::class, $rows->pluck('dish_id'));

        // A line priced in the second currency counts in the main one, at
        // the rate its order kept (as the order's total did).
        $sales = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->whereIn('order_items.order_id', $orders()->where('currency', $currency))
            ->whereIn('order_items.dish_id', array_keys($names))
            ->toBase()
            ->selectRaw('order_items.dish_id, SUM(ROUND(CASE WHEN order_items.currency IS NOT NULL AND orders.exchange_rate IS NOT NULL THEN order_items.line_total * orders.exchange_rate ELSE order_items.line_total END * 100)) as cents')
            ->groupBy('order_items.dish_id')
            ->pluck('cents', 'dish_id');

        return $rows
            ->filter(fn ($row) => isset($names[$row->dish_id]))
            ->map(fn ($row) => [
                'name' => $names[$row->dish_id],
                'quantity' => (int) $row->quantity,
                'sales' => $this->money((int) ($sales[$row->dish_id] ?? 0)),
            ])
            ->values()
            ->all();
    }

    /**
     * How the restaurant takes orders now. Only that channel's orders are
     * counted: a WhatsApp tap and an order placed in the menu are not the
     * same thing, and adding them up would say more than we know.
     */
    private function channel(): ?OrderChannel
    {
        return $this->range->restaurant->orderChannel();
    }

    /** @return Builder<Order> */
    private function ordersInRange(?CarbonImmutable $from, ?CarbonImmutable $until): Builder
    {
        // reorder(): the relation sorts by placed_at, which a grouped query
        // cannot carry.
        return $this->range->restaurant->orders()
            ->getQuery()
            ->reorder()
            ->when($from, fn ($query) => $query->where('placed_at', '>=', $from->utc()))
            ->when($until, fn ($query) => $query->where('placed_at', '<', $until->utc()));
    }

    /** @return Builder<Order> */
    private function menuOrdersInRange(?CarbonImmutable $from, ?CarbonImmutable $until): Builder
    {
        return $this->ordersInRange($from, $until)->where('channel', OrderChannel::Menu);
    }

    /**
     * The range's orders placed in the menu, cancelled ones left out.
     *
     * @return Builder<Order>
     */
    private function keptMenuOrders(): Builder
    {
        return $this->menuOrdersInRange($this->range->from, null)->where('status', '!=', OrderStatus::Cancelled->value);
    }

    private function money(int $cents): float
    {
        return round($cents / 100, 2);
    }

    /**
     * The middle value, which one forgotten order left open for a day
     * cannot drag the way it drags an average; null with none.
     *
     * @param  array<int, int>  $values
     */
    private function median(array $values): ?int
    {
        if ($values === []) {
            return null;
        }

        sort($values);
        $middle = intdiv(count($values), 2);

        return count($values) % 2 === 1
            ? $values[$middle]
            : (int) round(($values[$middle - 1] + $values[$middle]) / 2);
    }
}
