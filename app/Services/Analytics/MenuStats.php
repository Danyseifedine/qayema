<?php

namespace App\Services\Analytics;

use App\Enums\MenuEventType;
use App\Enums\OrderChannel;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuDay;
use App\Models\Restaurant;
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
    public const RANGES = ['today' => 1, '7d' => 7, '30d' => 30, '90d' => 90, 'all' => null];

    /** The ranges every package gets; the others need advanced analytics. */
    public const BASIC_RANGES = ['today', '7d', '30d'];

    /** "All time" can be years: this caps the chart, not the totals. */
    private const MAX_SERIES_DAYS = 366;

    private const TOP = 5;

    private const TOP_SEARCHES = 8;

    private readonly StatsRange $at;

    private readonly MenuOrderStats $orders;

    /** @var Collection<int, MenuDay>|null the days before, read once */
    private ?Collection $history = null;

    public function __construct(Restaurant $restaurant, private readonly string $range)
    {
        $this->at = StatsRange::of($restaurant, $range);
        $this->orders = new MenuOrderStats($this->at);
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
        $visits = MenuTraffic::count($this->inRange($this->at->restaurant->menuSessions(), 'viewed_at'), $this->at->today, countVisitors: $this->at->historyUntil === null);
        $orders = $this->takesOrders() ? $this->orders->totals($this->at->from) : null;

        return [
            'totals' => [
                'views' => $visits['views'] + $this->history()->sum('views'),
                'unique_visitors' => $this->at->historyUntil === null
                    ? $visits['unique_visitors']
                    : $this->visitorsWithHistory($this->inRange($this->at->restaurant->menuSessions(), 'viewed_at')->distinct()->pluck('session_id'), 'visitorSketch'),
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
            // The same totals for the period before, for "up 12%" (today:
            // yesterday up to this time). Null for "All time".
            'previous' => $this->previous(),
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
            'views' => $this->inRange($this->at->restaurant->menuSessions(), 'viewed_at')->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function advanced(): array
    {
        $visits = $this->inRange($this->at->restaurant->menuSessions(), 'viewed_at');

        return [
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
            'menu_orders' => $this->channel() === OrderChannel::Menu || $this->at->restaurant->dineInChannel() === OrderChannel::Menu ? $this->orders->menuOrders() : null,
        ];
    }

    // ---- Summary -------------------------------------------------------

    /**
     * One point per local day for the range, zeros included, so the chart has
     * no gaps; for today, one per hour so far ("2026-10-13T14:00").
     *
     * @return array<int, array{date: string, views: int, qr_scans: int}>
     */
    private function series(): array
    {
        if ($this->range === 'today') {
            return $this->hoursOfToday();
        }

        $start = $this->at->from ?? $this->firstVisitDay();
        $start = $start->max($this->at->today->subDays(self::MAX_SERIES_DAYS - 1));

        $days = [];

        for ($date = $start; $date->lte($this->at->today); $date = $date->addDay()) {
            $days[$date->toDateString()] = ['date' => $date->toDateString(), 'views' => 0, 'qr_scans' => 0];
        }

        foreach ($this->hourly($this->at->historyUntil?->max($start) ?? $start) as $bucket) {
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

    /** @return array<int, array{date: string, views: int, qr_scans: int}> */
    private function hoursOfToday(): array
    {
        $day = $this->at->today->toDateString();
        $hours = [];

        for ($hour = 0; $hour <= CarbonImmutable::now($this->at->timezone)->hour; $hour++) {
            $hours[$hour] = ['date' => sprintf('%sT%02d:00', $day, $hour), 'views' => 0, 'qr_scans' => 0];
        }

        foreach ($this->hourly($this->at->today) as $bucket) {
            if ($bucket['day'] === $day && isset($hours[$bucket['hour']])) {
                $hours[$bucket['hour']]['views'] += $bucket['views'];
                $hours[$bucket['hour']]['qr_scans'] += $bucket['qr_scans'];
            }
        }

        return array_values($hours);
    }

    private function firstVisitDay(): CarbonImmutable
    {
        if ($this->at->historyUntil !== null) {
            return CarbonImmutable::parse($this->history()->min('day'), $this->at->timezone)->startOfDay();
        }

        $first = $this->at->restaurant->menuSessions()->min('viewed_at');

        return $first === null
            ? $this->at->today
            : CarbonImmutable::parse($first, 'UTC')->setTimezone($this->at->timezone)->startOfDay();
    }

    /**
     * The same totals for the period just before this one, for "up 12%".
     * "All" has nothing before it.
     *
     * @return array{views: int, unique_visitors: int, qr_scans: int, orders: int|null}|null
     */
    private function previous(): ?array
    {
        if ($this->at->from === null) {
            return null;
        }

        [$start, $end] = $this->at->previousPeriod();

        $visits = $this->at->restaurant->menuSessions()
            ->where('viewed_at', '>=', $start->utc())
            ->where('viewed_at', '<', $end->utc());

        return [
            // Today is not in the period before.
            ...Arr::only(MenuTraffic::count($visits, $this->at->today), ['views', 'unique_visitors', 'qr_scans']),
            'orders' => $this->takesOrders() ? $this->orders->count($start, $end) : null,
        ];
    }

    // ---- Advanced ------------------------------------------------------

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

        foreach (MenuEventType::guestActions() as $type) {
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

        $counts = ($this->at->historyUntil === null ? $query->orderByDesc('total')->orderBy($column)->limit(self::TOP) : $query)
            ->pluck('total', 'id')
            ->map(fn ($total): int => (int) $total)
            ->all();

        $top = array_slice($this->withHistory($counts, $field), 0, self::TOP, true);
        $names = $this->at->names($model, array_keys($top));

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

        $counts = ($this->at->historyUntil === null ? $query->orderByDesc('total')->orderBy('value')->limit(self::TOP_SEARCHES) : $query)
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
            'visitors' => $this->at->historyUntil === null
                ? (clone $visits)->distinct('session_id')->count('session_id')
                : $this->visitorsWithHistory((clone $visits)->distinct()->pluck('session_id'), 'visitorSketch'),
            'carted' => $this->at->historyUntil === null
                ? $carted->distinct('session_id')->count('session_id')
                : $this->visitorsWithHistory($carted->distinct()->pluck('session_id'), 'cartedSketch'),
            'ordered' => $this->orders->count($this->at->from, null),
            'channel' => $this->channel()->value,
        ];
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
        return $this->at->restaurant->orderChannel();
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
        $local = new DateTimeZone($this->at->timezone);

        return $this->at->restaurant->menuSessions()
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

    private function inRange(HasMany $query, string $column): HasMany
    {
        return $query->when($this->rawFrom(), fn ($query, CarbonImmutable $from) => $query->where($column, '>=', $from->utc()));
    }

    /** Where the raw rows start counting: the range's start, or for "all" with history, the boundary. */
    private function rawFrom(): ?CarbonImmutable
    {
        return $this->at->from ?? $this->at->historyUntil;
    }

    /**
     * The days added up before the boundary, for "all"; none otherwise.
     *
     * @return Collection<int, MenuDay>
     */
    private function history(): Collection
    {
        return $this->history ??= $this->at->historyUntil === null
            ? new Collection
            // Everything but the sketches, which come merged (MenuDays::mergedSketch()).
            : MenuTraffic::history($this->at->restaurant, $this->at->historyUntil, ['id', 'day', 'views', 'qr_scans', 'hours', 'languages', 'actions', 'dishes_added', 'categories_opened', 'searches', 'missed_searches']);
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
        return MenuTraffic::visitors($this->at->restaurant, $rawIds, $this->at->historyUntil, $sketch);
    }

    private function events(): HasMany
    {
        return $this->inRange($this->at->restaurant->menuEvents(), 'occurred_at');
    }
}
