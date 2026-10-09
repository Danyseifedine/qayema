<?php

namespace App\Services\Analytics;

use App\Enums\MenuEventType;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;

/**
 * The owner's analytics for one range, from `menu_sessions`, `menu_events`
 * and `orders`.
 *
 * `summary()` is what every package sees; `advanced()` is the rest, behind the
 * `advanced_analytics` flag, with `menu_orders` on top of it for a restaurant
 * taking orders in the menu. A view is a row, a visitor is a distinct
 * session, a scan is a view with `via_qr`.
 *
 * Days and hours are the restaurant's own: rows are stored in UTC, grouped
 * by UTC hour in SQL (portable between MySQL and SQLite, and at most 24 rows a
 * day), then moved into the restaurant's timezone here. A timezone with a
 * half-hour offset lands each hour in the one it starts in.
 */
class MenuStats
{
    /** Range key => days, null meaning everything still kept. */
    public const RANGES = ['7d' => 7, '30d' => 30, '90d' => 90, 'all' => null];

    /** The ranges every package gets; the others need advanced analytics. */
    public const BASIC_RANGES = ['7d', '30d'];

    /** "All time" is whatever `stats:rollup` has not pruned; this caps the chart. */
    private const MAX_SERIES_DAYS = 366;

    private const TOP = 5;

    private const TOP_SEARCHES = 8;

    private readonly string $timezone;

    private readonly CarbonImmutable $today;

    /** Start of the range, in the restaurant's timezone; null for "all". */
    private readonly ?CarbonImmutable $from;

    public function __construct(private readonly Restaurant $restaurant, private readonly string $range)
    {
        $this->timezone = $restaurant->localTimezone();
        $this->today = CarbonImmutable::now($this->timezone)->startOfDay();

        $days = self::RANGES[$range] ?? null;
        $this->from = $days === null ? null : $this->today->subDays($days - 1);
    }

    public static function isBasicRange(string $range): bool
    {
        return in_array($range, self::BASIC_RANGES, true);
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(): array
    {
        // Every range ends now, so today's views are a part of it: counted
        // in the same pass. The orders and those done, likewise, in one.
        $visits = $this->visitTotals($this->inRange($this->restaurant->menuSessions(), 'viewed_at'), $this->today);
        $orders = $this->takesOrders() ? $this->orderTotals($this->from) : null;

        return [
            'totals' => [
                'views' => $visits['views'],
                'unique_visitors' => $visits['unique_visitors'],
                'qr_scans' => $visits['qr_scans'],
                'views_today' => $visits['since'],
                'orders' => $orders['kept'] ?? null,
                // Only an order placed in the menu can be marked done; a
                // WhatsApp one is never known to be.
                'orders_done' => $this->channel() === OrderChannel::Menu ? $orders['done'] ?? 0 : null,
            ],
            // Which orders "orders" counts: taps that opened WhatsApp, or
            // orders placed in the menu. Null when the restaurant takes none.
            'order_channel' => $this->channel()?->value,
            'series' => $this->series(),
        ];
    }

    /**
     * The one number a package without analytics still sees: menu views in
     * the range.
     *
     * @return array{views: int}
     */
    public function teaser(): array
    {
        return [
            'views' => $this->inRange($this->restaurant->menuSessions(), 'viewed_at')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function advanced(): array
    {
        $visits = $this->inRange($this->restaurant->menuSessions(), 'viewed_at');

        return [
            'previous' => $this->previous(),
            ...$this->busyTimes(),
            'languages' => $this->breakdown($visits, 'locale'),
            'actions' => $this->actions(),
            'top_added' => $this->topDishesAdded(),
            'top_categories' => $this->topCategories(),
            'searches' => $this->searches([MenuEventType::Search, MenuEventType::SearchMiss]),
            'missed_searches' => $this->searches([MenuEventType::SearchMiss]),
            'funnel' => $this->takesOrders() ? $this->funnel($visits) : null,
            // Orders at the table are placed in the menu whatever the channel.
            'menu_orders' => $this->channel() === OrderChannel::Menu || $this->restaurant->takesDineIn() ? $this->menuOrders() : null,
        ];
    }

    // ---- Summary -------------------------------------------------------

    /**
     * One point per local day for the range, zeros included, so the chart has
     * no gaps.
     *
     * @return array<int, array{date: string, views: int, qr_scans: int}>
     */
    private function series(): array
    {
        $start = $this->from ?? $this->firstVisitDay();
        $start = $start->max($this->today->subDays(self::MAX_SERIES_DAYS - 1));

        $days = [];

        for ($date = $start; $date->lte($this->today); $date = $date->addDay()) {
            $days[$date->toDateString()] = ['date' => $date->toDateString(), 'views' => 0, 'qr_scans' => 0];
        }

        foreach ($this->hourly($start) as $bucket) {
            $day = $bucket['at']->toDateString();

            if (isset($days[$day])) {
                $days[$day]['views'] += $bucket['views'];
                $days[$day]['qr_scans'] += $bucket['qr_scans'];
            }
        }

        return array_values($days);
    }

    private function firstVisitDay(): CarbonImmutable
    {
        $first = $this->restaurant->menuSessions()->min('viewed_at');

        return $first === null
            ? $this->today
            : CarbonImmutable::parse($first, 'UTC')->setTimezone($this->timezone)->startOfDay();
    }

    // ---- Advanced ------------------------------------------------------

    /**
     * The same totals for the period just before this one, for "up 12%".
     * "All" has nothing before it.
     *
     * @return array{views: int, unique_visitors: int, qr_scans: int, orders: int|null}|null
     */
    private function previous(): ?array
    {
        if ($this->from === null) {
            return null;
        }

        $days = (int) self::RANGES[$this->range];
        $start = $this->from->subDays($days);

        $visits = $this->restaurant->menuSessions()
            ->where('viewed_at', '>=', $start->utc())
            ->where('viewed_at', '<', $this->from->utc());

        return [
            // Today is not in the period before.
            ...Arr::except($this->visitTotals($visits), 'since'),
            'orders' => $this->takesOrders() ? $this->orderCount($start, $this->from) : null,
        ];
    }

    /**
     * Views by local hour of day (0 to 23) and by weekday (Monday first).
     *
     * @return array{hours: array<int, int>, weekdays: array<int, int>}
     */
    private function busyTimes(): array
    {
        $hours = array_fill(0, 24, 0);
        $weekdays = array_fill(0, 7, 0);

        foreach ($this->hourly($this->from) as $bucket) {
            $hours[$bucket['at']->hour] += $bucket['views'];
            $weekdays[$bucket['at']->dayOfWeekIso - 1] += $bucket['views'];
        }

        return ['hours' => $hours, 'weekdays' => $weekdays];
    }

    /**
     * @return array<int, array{key: string, count: int}>
     */
    private function breakdown(HasMany $visits, string $column): array
    {
        return (clone $visits)
            ->selectRaw("COALESCE({$column}, 'unknown') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->orderByDesc('total')
            ->orderBy('bucket')
            ->get()
            ->map(fn ($row) => ['key' => (string) $row->bucket, 'count' => (int) $row->total])
            ->all();
    }

    /**
     * How often guests did each thing, one count per type.
     *
     * @return array<string, int>
     */
    private function actions(): array
    {
        $counts = $this->events()
            ->selectRaw('type, COUNT(*) as total')
            ->groupBy('type')
            ->pluck('total', 'type');

        $actions = [];

        foreach (MenuEventType::cases() as $type) {
            $actions[$type->value] = (int) ($counts[$type->value] ?? 0);
        }

        return $actions;
    }

    /**
     * @return array<int, array{name: string, count: int}>
     */
    private function topDishesAdded(): array
    {
        $rows = $this->events()
            ->where('type', MenuEventType::DishAdd->value)
            ->whereNotNull('dish_id')
            ->selectRaw('dish_id, COUNT(*) as total')
            ->groupBy('dish_id')
            ->orderByDesc('total')
            ->orderBy('dish_id')
            ->limit(self::TOP)
            ->get();

        $names = $this->names(Dish::class, $rows->pluck('dish_id'));

        return $rows
            ->filter(fn ($row) => isset($names[$row->dish_id]))
            ->map(fn ($row) => ['name' => $names[$row->dish_id], 'count' => (int) $row->total])
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{name: string, count: int}>
     */
    private function topCategories(): array
    {
        $rows = $this->events()
            ->where('type', MenuEventType::CategoryOpen->value)
            ->whereNotNull('category_id')
            ->selectRaw('category_id, COUNT(*) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->orderBy('category_id')
            ->limit(self::TOP)
            ->get();

        $names = $this->names(Category::class, $rows->pluck('category_id'));

        return $rows
            ->filter(fn ($row) => isset($names[$row->category_id]))
            ->map(fn ($row) => ['name' => $names[$row->category_id], 'count' => (int) $row->total])
            ->values()
            ->all();
    }

    /**
     * @param  array<int, MenuEventType>  $types
     * @return array<int, array{term: string, count: int}>
     */
    private function searches(array $types): array
    {
        return $this->events()
            ->whereIn('type', array_map(fn (MenuEventType $type) => $type->value, $types))
            ->whereNotNull('value')
            ->selectRaw('value, COUNT(*) as total')
            ->groupBy('value')
            ->orderByDesc('total')
            ->orderBy('value')
            ->limit(self::TOP_SEARCHES)
            ->get()
            ->map(fn ($row) => ['term' => (string) $row->value, 'count' => (int) $row->total])
            ->all();
    }

    /**
     * From opening the menu to ordering: visitors, then those who put
     * something in the cart, then orders placed (or, on WhatsApp, sent
     * there: `channel` says which).
     *
     * @return array{visitors: int, carted: int, ordered: int, channel: string}
     */
    private function funnel(HasMany $visits): array
    {
        return [
            'visitors' => (clone $visits)->distinct('session_id')->count('session_id'),
            'carted' => $this->events()
                ->where('type', MenuEventType::DishAdd->value)
                ->distinct('session_id')
                ->count('session_id'),
            'ordered' => $this->orderCount($this->from, null),
            'channel' => $this->channel()->value,
        ];
    }

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
    private function menuOrders(): array
    {
        $currency = (string) $this->restaurant->currency;
        $statuses = array_fill_keys(array_column(OrderStatus::cases(), 'value'), 0);
        $fulfilment = [];
        $hours = array_fill(0, 24, 0);
        $weekdays = array_fill(0, 7, 0);
        $phones = [];
        $waits = [];
        $cents = 0;
        $paid = 0;

        $orders = $this->menuOrdersInRange($this->from, null)
            ->get(['status', 'fulfilment', 'currency', 'total', 'guest_phone', 'placed_at', 'accepted_at', 'ready_at', 'closed_at']);

        foreach ($orders as $order) {
            $statuses[$order->status->value]++;

            if ($order->status === OrderStatus::Cancelled) {
                continue;
            }

            if ($order->fulfilment !== null) {
                $fulfilment[$order->fulfilment->value] = ($fulfilment[$order->fulfilment->value] ?? 0) + 1;
            }

            $placed = CarbonImmutable::instance($order->placed_at)->setTimezone($this->timezone);
            $hours[$placed->hour]++;
            $weekdays[$placed->dayOfWeekIso - 1]++;

            if ($order->guest_phone) {
                $phones[$order->guest_phone] = ($phones[$order->guest_phone] ?? 0) + 1;
            }

            // Taken on: accepted, or moved straight to ready or done. A
            // restaurant that skips "accepted" still answered the order.
            $answered = $order->accepted_at ?? $order->ready_at ?? ($order->status === OrderStatus::Done ? $order->closed_at : null);

            if ($answered !== null) {
                $waits[] = max(0, (int) round($order->placed_at->diffInSeconds($answered, true) / 60));
            }

            if ($order->currency === $currency) {
                $cents += $this->cents($order->total);
                $paid++;
            }
        }

        arsort($fulfilment);

        return [
            'currency' => $currency,
            'sales' => $this->money($cents),
            'average' => $paid > 0 ? $this->money(intdiv($cents + intdiv($paid, 2), $paid)) : null,
            // Null for "All time", which has no period before it.
            'previous_sales' => $this->from === null ? null : $this->previousSales($currency),
            'statuses' => $statuses,
            'fulfilment' => array_map(
                fn (string $key, int $count): array => ['key' => $key, 'count' => $count],
                array_keys($fulfilment),
                $fulfilment,
            ),
            'guests' => count($phones),
            'returning_guests' => count(array_filter($phones, fn (int $count): bool => $count > 1)),
            'minutes_to_accept' => $this->median($waits),
            'hours' => $hours,
            'weekdays' => $weekdays,
            'top_ordered' => $this->topDishesOrdered($currency),
        ];
    }

    /** What the menu's orders came to in the period just before this one. */
    private function previousSales(string $currency): float
    {
        $days = (int) self::RANGES[$this->range];

        $total = $this->menuOrdersInRange($this->from->subDays($days), $this->from)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->where('currency', $currency)
            ->pluck('total')
            ->sum(fn ($total): int => $this->cents($total));

        return $this->money($total);
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
        $orders = fn () => $this->menuOrdersInRange($this->from, null)
            ->where('status', '!=', OrderStatus::Cancelled->value)
            ->select('id')
            ->getQuery();

        $rows = OrderItem::query()
            ->whereIn('order_id', $orders())
            ->whereNotNull('dish_id')
            ->selectRaw('dish_id, SUM(quantity) as quantity')
            ->groupBy('dish_id')
            ->orderByDesc('quantity')
            ->orderBy('dish_id')
            ->limit(self::TOP)
            ->get();

        $names = $this->names(Dish::class, $rows->pluck('dish_id'));

        $sales = OrderItem::query()
            ->whereIn('order_id', $orders()->where('currency', $currency))
            ->whereIn('dish_id', array_keys($names))
            ->get(['dish_id', 'line_total'])
            ->groupBy('dish_id')
            ->map(fn ($lines): int => $lines->sum(fn (OrderItem $line): int => $this->cents($line->line_total)));

        return $rows
            ->filter(fn ($row) => isset($names[$row->dish_id]))
            ->map(fn ($row) => [
                'name' => $names[$row->dish_id],
                'quantity' => (int) $row->quantity,
                'sales' => $this->money($sales[$row->dish_id] ?? 0),
            ])
            ->values()
            ->all();
    }

    // ---- Building blocks -------------------------------------------------

    private function takesOrders(): bool
    {
        return $this->channel() !== null;
    }

    /**
     * How the restaurant takes orders now. Only that channel's orders are
     * counted: a WhatsApp tap and an order placed in the menu are not the
     * same thing, and adding them up would say more than we know.
     */
    private function channel(): ?OrderChannel
    {
        return $this->restaurant->orderChannel();
    }

    /**
     * Views per UTC hour since `$from`, each moved into the restaurant's
     * timezone.
     *
     * @return array<int, array{at: CarbonImmutable, views: int, qr_scans: int}>
     */
    private function hourly(?CarbonImmutable $from): array
    {
        return $this->restaurant->menuSessions()
            ->when($from, fn ($query) => $query->where('viewed_at', '>=', $from->utc()))
            ->selectRaw('SUBSTR(viewed_at, 1, 13) as bucket, COUNT(*) as views, SUM(CASE WHEN via_qr THEN 1 ELSE 0 END) as qr_scans')
            ->groupBy('bucket')
            ->get()
            ->map(fn ($row) => [
                'at' => CarbonImmutable::createFromFormat('Y-m-d H', (string) $row->bucket, 'UTC')->setTimezone($this->timezone),
                'views' => (int) $row->views,
                'qr_scans' => (int) $row->qr_scans,
            ])
            ->all();
    }

    /**
     * Views, distinct visitors and QR scans of some visits, in one pass over
     * them rather than three counts; with `$since`, the views from then on
     * too (today's).
     *
     * @return array{views: int, unique_visitors: int, qr_scans: int, since: int}
     */
    private function visitTotals(HasMany $visits, ?CarbonImmutable $since = null): array
    {
        $totals = $visits->toBase()
            ->selectRaw(
                'count(*) as views, count(distinct session_id) as visitors, sum(case when via_qr then 1 else 0 end) as scans, sum(case when viewed_at >= ? then 1 else 0 end) as since',
                [($since ?? $this->today)->utc()->toDateTimeString()],
            )
            ->first();

        return [
            'views' => (int) $totals->views,
            'unique_visitors' => (int) $totals->visitors,
            'qr_scans' => (int) $totals->scans,
            'since' => (int) $totals->since,
        ];
    }

    /**
     * The current channel's orders from `$from`: those not cancelled, and
     * those done, in one pass.
     *
     * @return array{kept: int, done: int}
     */
    private function orderTotals(?CarbonImmutable $from): array
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

    private function inRange(HasMany $query, string $column): HasMany
    {
        return $query->when($this->from, fn ($query) => $query->where($column, '>=', $this->from->utc()));
    }

    private function events(): HasMany
    {
        return $this->inRange($this->restaurant->menuEvents(), 'occurred_at');
    }

    private function ordersInRange(?CarbonImmutable $from, ?CarbonImmutable $until): HasMany
    {
        // reorder(): the relation sorts by placed_at, which a grouped query
        // cannot carry.
        return $this->restaurant->orders()
            ->reorder()
            ->when($from, fn ($query) => $query->where('placed_at', '>=', $from->utc()))
            ->when($until, fn ($query) => $query->where('placed_at', '<', $until->utc()));
    }

    private function menuOrdersInRange(?CarbonImmutable $from, ?CarbonImmutable $until): HasMany
    {
        return $this->ordersInRange($from, $until)->where('channel', OrderChannel::Menu);
    }

    /** A decimal amount as whole cents, so sums never drift on floats. */
    private function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
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

    /** Orders of the current channel, cancelled ones left out, or only those of one status. */
    private function orderCount(?CarbonImmutable $from, ?CarbonImmutable $until, ?OrderStatus $status = null): int
    {
        return $this->ordersInRange($from, $until)
            ->where('channel', $this->channel())
            ->when(
                $status,
                fn ($query, OrderStatus $status) => $query->where('status', $status->value),
                fn ($query) => $query->where('status', '!=', OrderStatus::Cancelled->value),
            )
            ->count();
    }

    /**
     * Current names, in the owner's language, for dishes or categories.
     *
     * @param  class-string<Dish|Category>  $model
     * @param  iterable<int|null>  $ids
     * @return array<int, string>
     */
    private function names(string $model, iterable $ids): array
    {
        $ids = collect($ids)->filter()->unique()->values();

        if ($ids->isEmpty()) {
            return [];
        }

        $locale = MenuLanguages::default($this->restaurant);
        $main = MenuLanguages::main($this->restaurant);

        return $model::query()
            ->where('restaurant_id', $this->restaurant->id)
            ->whereKey($ids->all())
            ->get()
            ->mapWithKeys(fn (Dish|Category $row) => [
                $row->getKey() => MenuLanguages::text($row, 'name', $locale, $main),
            ])
            ->all();
    }
}
