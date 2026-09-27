<?php

namespace App\Services\Global;

use App\Enums\Feature;
use App\Enums\MenuEventType;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * The owner's analytics for one range, from `menu_sessions`, `menu_events`
 * and `orders`.
 *
 * `summary()` is what every package sees; `advanced()` is the rest, behind the
 * `advanced_analytics` flag. A view is a row, a visitor is a distinct session,
 * a scan is a view with `via_qr`.
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
        $this->timezone = self::timezoneOf($restaurant);
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
        $visits = $this->inRange($this->restaurant->statistics(), 'viewed_at');

        return [
            'range' => $this->range,
            'timezone' => $this->timezone,
            'totals' => [
                'views' => (clone $visits)->count(),
                'unique_visitors' => (clone $visits)->distinct('session_id')->count('session_id'),
                'qr_scans' => (clone $visits)->where('via_qr', true)->count(),
                'views_today' => $this->restaurant->statistics()->where('viewed_at', '>=', $this->today->utc())->count(),
                'orders' => $this->takesOrders() ? $this->orderCount($this->from, null) : null,
            ],
            'series' => $this->series(),
            'last_visit_at' => $this->restaurant->statistics()->max('viewed_at'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function advanced(): array
    {
        $visits = $this->inRange($this->restaurant->statistics(), 'viewed_at');

        return [
            'range' => $this->range,
            'previous' => $this->previous(),
            ...$this->busyTimes(),
            'languages' => $this->breakdown($visits, 'locale'),
            'actions' => $this->actions(),
            'top_added' => $this->topDishesAdded(),
            'top_categories' => $this->topCategories(),
            'searches' => $this->searches([MenuEventType::Search, MenuEventType::SearchMiss]),
            'missed_searches' => $this->searches([MenuEventType::SearchMiss]),
            'funnel' => $this->takesOrders() ? $this->funnel($visits) : null,
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
        $first = $this->restaurant->statistics()->min('viewed_at');

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

        $visits = $this->restaurant->statistics()
            ->where('viewed_at', '>=', $start->utc())
            ->where('viewed_at', '<', $this->from->utc());

        return [
            'views' => (clone $visits)->count(),
            'unique_visitors' => (clone $visits)->distinct('session_id')->count('session_id'),
            'qr_scans' => (clone $visits)->where('via_qr', true)->count(),
            'orders' => $this->takesOrders() ? $this->orderCount($start, $this->from) : null,
        ];
    }

    /**
     * Views by local hour of day (0–23) and by weekday (Monday first).
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
     * something in the cart, then orders placed.
     *
     * @return array{visitors: int, carted: int, ordered: int}
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
        ];
    }

    // ---- Building blocks -------------------------------------------------

    private function takesOrders(): bool
    {
        return $this->restaurant->entitlements()->can(Feature::Ordering);
    }

    /**
     * Views per UTC hour since `$from`, each moved into the restaurant's
     * timezone.
     *
     * @return array<int, array{at: CarbonImmutable, views: int, qr_scans: int}>
     */
    private function hourly(?CarbonImmutable $from): array
    {
        return $this->restaurant->statistics()
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

    private function orderCount(?CarbonImmutable $from, ?CarbonImmutable $until): int
    {
        return $this->ordersInRange($from, $until)
            ->where('status', '!=', OrderStatus::Cancelled->value)
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

        return $model::query()
            ->where('restaurant_id', $this->restaurant->id)
            ->whereKey($ids->all())
            ->get()
            ->mapWithKeys(fn (Dish|Category $row) => [
                $row->getKey() => MenuLanguages::text($row, 'name', $locale),
            ])
            ->all();
    }

    private static function timezoneOf(Restaurant $restaurant): string
    {
        $timezone = $restaurant->timezone ?: config('app.timezone', 'UTC');

        return in_array($timezone, timezone_identifiers_list(), true) ? $timezone : 'UTC';
    }
}
