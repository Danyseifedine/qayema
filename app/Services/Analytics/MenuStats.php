<?php

namespace App\Services\Analytics;

use App\Enums\MenuEventType;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuDay;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\HyperLogLog;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use DateTimeZone;
use Illuminate\Database\Eloquent\Collection;
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
 *
 * "All time" reaches past the raw rows, which `stats:rollup` deletes after 6
 * months: the days before MenuDays::boundary() come from `menu_days` (one
 * row a day, added up before the raw rows went), the rest from the raw rows,
 * so no day is counted twice. Visitors over both come from HyperLogLog
 * sketches; with no days added up yet before the boundary, everything is
 * read from the raw rows as before, exactly.
 */
class MenuStats
{
    /** Range key => days, null meaning everything still kept. */
    public const RANGES = ['7d' => 7, '30d' => 30, '90d' => 90, 'all' => null];

    /** The ranges every package gets; the others need advanced analytics. */
    public const BASIC_RANGES = ['7d', '30d'];

    /** "All time" can be years: this caps the chart, not the totals. */
    private const MAX_SERIES_DAYS = 366;

    private const TOP = 5;

    private const TOP_SEARCHES = 8;

    private readonly string $timezone;

    private readonly CarbonImmutable $today;

    /** Start of the range, in the restaurant's timezone; null for "all". */
    private readonly ?CarbonImmutable $from;

    /**
     * For "all" with days added up before MenuDays::boundary(): the boundary,
     * where the raw rows take over from `menu_days`. Null otherwise.
     */
    private readonly ?CarbonImmutable $historyUntil;

    /** @var Collection<int, MenuDay>|null the days before, read once */
    private ?Collection $history = null;

    public function __construct(private readonly Restaurant $restaurant, private readonly string $range)
    {
        $this->timezone = $restaurant->localTimezone();
        $this->today = CarbonImmutable::now($this->timezone)->startOfDay();

        $days = self::RANGES[$range] ?? null;
        $this->from = $days === null ? null : $this->today->subDays($days - 1);

        $boundary = MenuDays::boundary($restaurant);
        $this->historyUntil = $this->from === null
            && $restaurant->menuDays()->where('day', '<', $boundary->toDateString())->exists()
            ? $boundary
            : null;
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
        $visits = $this->visitTotals($this->inRange($this->restaurant->menuSessions(), 'viewed_at'), $this->today, countVisitors: $this->historyUntil === null);
        $orders = $this->takesOrders() ? $this->orderTotals($this->from) : null;

        return [
            'totals' => [
                'views' => $visits['views'] + $this->history()->sum('views'),
                'unique_visitors' => $this->historyUntil === null
                    ? $visits['unique_visitors']
                    : $this->visitorsWithHistory($this->inRange($this->restaurant->menuSessions(), 'viewed_at')->distinct()->pluck('session_id'), 'visitorSketch'),
                'qr_scans' => $visits['qr_scans'] + $this->history()->sum('qr_scans'),
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
            // Orders at the table may be placed in the menu whatever the
            // channel of delivery and pickup.
            'menu_orders' => $this->channel() === OrderChannel::Menu || $this->restaurant->dineInChannel() === OrderChannel::Menu ? $this->menuOrders() : null,
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

        foreach ($this->hourly($this->historyUntil?->max($start) ?? $start) as $bucket) {
            $day = $bucket['day'];

            if (isset($days[$day])) {
                $days[$day]['views'] += $bucket['views'];
                $days[$day]['qr_scans'] += $bucket['qr_scans'];
            }
        }

        foreach ($this->history() as $past) {
            $day = $past->day->toDateString();

            if (isset($days[$day])) {
                $days[$day]['views'] += $past->views;
                $days[$day]['qr_scans'] += $past->qr_scans;
            }
        }

        return array_values($days);
    }

    private function firstVisitDay(): CarbonImmutable
    {
        if ($this->historyUntil !== null) {
            return CarbonImmutable::parse($this->history()->min('day'), $this->timezone)->startOfDay();
        }

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

        foreach ($this->hourly($this->rawFrom()) as $bucket) {
            $hours[$bucket['hour']] += $bucket['views'];
            $weekdays[$bucket['weekday'] - 1] += $bucket['views'];
        }

        foreach ($this->history() as $past) {
            foreach ($past->hours as $hour => $views) {
                $hours[(int) $hour] += (int) $views;
            }
            $weekdays[$past->day->dayOfWeekIso - 1] += $past->views;
        }

        return ['hours' => $hours, 'weekdays' => $weekdays];
    }

    /**
     * @return array<int, array{key: string, count: int}>
     */
    private function breakdown(HasMany $visits, string $column): array
    {
        $counts = (clone $visits)
            ->selectRaw("COALESCE({$column}, 'unknown') as bucket, COUNT(*) as total")
            ->groupBy('bucket')
            ->pluck('total', 'bucket')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $counts = $this->withHistory($counts, 'languages');

        return array_map(fn ($key, int $count): array => ['key' => (string) $key, 'count' => $count], array_keys($counts), $counts);
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

        foreach ($this->history() as $past) {
            foreach ($past->actions as $type => $count) {
                if (isset($actions[$type])) {
                    $actions[$type] += (int) $count;
                }
            }
        }

        return $actions;
    }

    /**
     * @return array<int, array{name: string, count: int}>
     */
    private function topDishesAdded(): array
    {
        return $this->topNamed(MenuEventType::DishAdd, 'dish_id', 'dishes_added', Dish::class);
    }

    /**
     * @return array<int, array{name: string, count: int}>
     */
    private function topCategories(): array
    {
        return $this->topNamed(MenuEventType::CategoryOpen, 'category_id', 'categories_opened', Category::class);
    }

    /**
     * The dishes or categories guests went for most, under today's names; one
     * deleted since is left out. With history, the counts of every id are
     * added up first, so a dish popular a year ago still ranks.
     *
     * @param  'dish_id'|'category_id'  $column
     * @param  'dishes_added'|'categories_opened'  $field
     * @param  class-string<Dish|Category>  $model
     * @return array<int, array{name: string, count: int}>
     */
    private function topNamed(MenuEventType $type, string $column, string $field, string $model): array
    {
        $query = $this->events()
            ->where('type', $type->value)
            ->whereNotNull($column)
            ->selectRaw("{$column} as id, COUNT(*) as total")
            ->groupBy($column);

        $counts = ($this->historyUntil === null ? $query->orderByDesc('total')->orderBy($column)->limit(self::TOP) : $query)
            ->pluck('total', 'id')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $top = array_slice($this->withHistory($counts, $field), 0, self::TOP, true);
        $names = $this->names($model, array_keys($top));

        return array_values(array_filter(array_map(
            fn ($id, int $count): ?array => isset($names[$id]) ? ['name' => $names[$id], 'count' => $count] : null,
            array_keys($top),
            $top,
        )));
    }

    /**
     * @param  array<int, MenuEventType>  $types
     * @return array<int, array{term: string, count: int}>
     */
    private function searches(array $types): array
    {
        $query = $this->events()
            ->whereIn('type', array_map(fn (MenuEventType $type) => $type->value, $types))
            ->whereNotNull('value')
            ->selectRaw('value, COUNT(*) as total')
            ->groupBy('value');

        $counts = ($this->historyUntil === null ? $query->orderByDesc('total')->orderBy('value')->limit(self::TOP_SEARCHES) : $query)
            ->pluck('total', 'value')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $counts = $this->withHistory($counts, in_array(MenuEventType::Search, $types, true) ? 'searches' : 'missed_searches');

        return array_map(
            fn ($term, int $count): array => ['term' => (string) $term, 'count' => $count],
            array_keys(array_slice($counts, 0, self::TOP_SEARCHES, true)),
            array_slice($counts, 0, self::TOP_SEARCHES, true),
        );
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
        $carted = $this->events()->where('type', MenuEventType::DishAdd->value);

        return [
            'visitors' => $this->historyUntil === null
                ? (clone $visits)->distinct('session_id')->count('session_id')
                : $this->visitorsWithHistory((clone $visits)->distinct()->pluck('session_id'), 'visitorSketch'),
            'carted' => $this->historyUntil === null
                ? $carted->distinct('session_id')->count('session_id')
                : $this->visitorsWithHistory($carted->distinct()->pluck('session_id'), 'cartedSketch'),
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
     * timezone: its local day, hour and weekday (1 Monday to 7 Sunday).
     * Plain DateTime, not Carbon: six months is some 4,000 buckets.
     *
     * @return array<int, array{day: string, hour: int, weekday: int, views: int, qr_scans: int}>
     */
    private function hourly(?CarbonImmutable $from): array
    {
        $utc = new DateTimeZone('UTC');
        $local = new DateTimeZone($this->timezone);

        return $this->restaurant->menuSessions()
            ->when($from, fn ($query) => $query->where('viewed_at', '>=', $from->utc()))
            ->selectRaw('SUBSTR(viewed_at, 1, 13) as bucket, COUNT(*) as views, SUM(CASE WHEN via_qr THEN 1 ELSE 0 END) as qr_scans')
            ->groupBy('bucket')
            ->toBase()
            ->get()
            ->map(function ($row) use ($utc, $local): array {
                [$day, $hour, $weekday] = explode(' ', (new DateTimeImmutable($row->bucket.':00:00', $utc))->setTimezone($local)->format('Y-m-d G N'));

                return ['day' => $day, 'hour' => (int) $hour, 'weekday' => (int) $weekday, 'views' => (int) $row->views, 'qr_scans' => (int) $row->qr_scans];
            })
            ->all();
    }

    /**
     * Views, distinct visitors and QR scans of some visits, in one pass over
     * them rather than three counts; with `$since`, the views from then on
     * too (today's).
     *
     * @return array{views: int, unique_visitors: int, qr_scans: int, since: int}
     */
    private function visitTotals(HasMany $visits, ?CarbonImmutable $since = null, bool $countVisitors = true): array
    {
        // Without the distinct count when the caller counts visitors with
        // sketches: the costliest part of the pass, for a number unused.
        $visitors = $countVisitors ? 'count(distinct session_id)' : '0';

        $totals = $visits->toBase()
            ->selectRaw(
                "count(*) as views, {$visitors} as visitors, sum(case when via_qr then 1 else 0 end) as scans, sum(case when viewed_at >= ? then 1 else 0 end) as since",
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
        return $query->when($this->rawFrom(), fn ($query, CarbonImmutable $from) => $query->where($column, '>=', $from->utc()));
    }

    /** Where the raw rows start counting: the range's start, or for "all" with history, the boundary. */
    private function rawFrom(): ?CarbonImmutable
    {
        return $this->from ?? $this->historyUntil;
    }

    /**
     * The days added up before the boundary, for "all"; none otherwise.
     *
     * @return Collection<int, MenuDay>
     */
    private function history(): Collection
    {
        return $this->history ??= $this->historyUntil === null
            ? new Collection
            // Everything but the sketches, which come merged (MenuDays::mergedSketch()).
            : $this->restaurant->menuDays()
                ->where('day', '<', $this->historyUntil->toDateString())
                ->orderBy('day')
                ->get(['id', 'day', 'views', 'qr_scans', 'hours', 'languages', 'actions', 'dishes_added', 'categories_opened', 'searches', 'missed_searches']);
    }

    /**
     * Raw counts with the history's counts of the same field added, most first
     * (then by key, as the raw queries sort).
     *
     * @param  array<int|string, int>  $counts
     * @return array<int|string, int>
     */
    private function withHistory(array $counts, string $field): array
    {
        foreach ($this->history() as $past) {
            foreach ($past->{$field} as $key => $count) {
                $counts[$key] = ($counts[$key] ?? 0) + (int) $count;
            }
        }

        uksort($counts, fn ($a, $b): int => [$counts[$b], (string) $a] <=> [$counts[$a], (string) $b]);

        return $counts;
    }

    /**
     * Distinct visitors over the raw rows and the history together: the raw
     * ids into a sketch, merged with each day's.
     *
     * @param  iterable<string>  $rawIds
     * @param  'visitorSketch'|'cartedSketch'  $sketch
     */
    private function visitorsWithHistory(iterable $rawIds, string $sketch): int
    {
        return HyperLogLog::of($rawIds)->merge(MenuDays::mergedSketch($this->restaurant, $this->historyUntil, $sketch))->count();
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

        $read = MenuLanguages::reader($this->restaurant, MenuLanguages::default($this->restaurant));

        return $model::query()
            ->where('restaurant_id', $this->restaurant->id)
            ->whereKey($ids->all())
            ->get()
            ->mapWithKeys(fn (Dish|Category $row) => [
                $row->getKey() => $read($row, 'name'),
            ])
            ->all();
    }
}
