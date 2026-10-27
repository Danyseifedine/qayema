<?php

namespace App\Services\Analytics;

use App\Models\MenuDay;
use App\Models\MenuSession;
use App\Models\Restaurant;
use App\Support\HyperLogLog;
use App\Support\PerRequest;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A menu's visits as the admin sees them: the totals of one restaurant
 * (totals(), the user page and the admin API), its QR scans (qrScans()) and
 * the views and scans of each row of a list (withTotals()). Like "All time"
 * in MenuStats, older days come from `menu_days`, so the numbers outlive the
 * raw rows, which `stats:rollup` deletes after 6 months.
 *
 * Also the passes MenuStats shares: count(), history() and visitors().
 */
class MenuTraffic
{
    /**
     * The days of raw rows a list counts per restaurant; every day before
     * comes from `menu_days`. More than the one day the nightly roll-up can
     * be behind, so a late or missed night loses nothing.
     */
    public const LIST_RAW_DAYS = 7;

    public function __construct(private readonly Restaurant $restaurant) {}

    /**
     * The menu's visits at a glance, in one pass: views, distinct visitors,
     * QR scans, today's views (the restaurant's own day) and the last visit.
     *
     * @return array{views: int, visitors: int, scans: int, today: int, last: string|null}
     */
    public function totals(): array
    {
        $today = CarbonImmutable::now($this->restaurant->localTimezone())->startOfDay();
        $boundary = MenuDays::boundary($this->restaurant);
        $history = self::history($this->restaurant, $boundary, ['id', 'day', 'views', 'qr_scans']);
        $from = $history->isEmpty() ? null : $boundary;

        $visits = function () use ($from): HasMany {
            $visits = $this->restaurant->menuSessions();

            if ($from !== null) {
                $visits->where('viewed_at', '>=', $from->utc()->toDateTimeString());
            }

            return $visits;
        };

        // With history the visitors come from sketches: no distinct count here.
        $totals = self::count($visits(), $today, countVisitors: $from === null);

        return [
            'views' => $totals['views'] + $history->sum('views'),
            // Days added up before the raw rows (MenuDays): their visitors and
            // the raw ones counted together, a returning guest once.
            'visitors' => $from === null
                ? $totals['unique_visitors']
                : self::visitors($this->restaurant, $visits()->distinct()->pluck('session_id'), $boundary, 'visitorSketch'),
            'scans' => $totals['qr_scans'] + $history->sum('qr_scans'),
            'today' => $totals['since'],
            'last' => $totals['last'],
        ];
    }

    /**
     * Visits that came from the QR code: today, this week, this month (the
     * restaurant's own days, like the analytics) and ever. One pass over
     * plain ranges, so the (restaurant_id, viewed_at) index does the work;
     * "ever" adds the days added up before the raw rows (MenuDays).
     *
     * @return array{today: int, week: int, month: int, total: int}
     */
    public function qrScans(): array
    {
        $now = CarbonImmutable::now($this->restaurant->localTimezone());
        $since = fn (CarbonImmutable $start): string => $start->utc()->toDateTimeString();
        $boundary = MenuDays::boundary($this->restaurant);
        $history = self::history($this->restaurant, $boundary, ['id', 'day', 'qr_scans']);

        $counts = $this->restaurant->menuSessions()
            ->where('via_qr', true)
            ->when($history->isNotEmpty(), fn ($query) => $query->where('viewed_at', '>=', $since($boundary)))
            ->selectRaw(
                'count(*) as total,
                sum(case when viewed_at >= ? then 1 else 0 end) as today,
                sum(case when viewed_at >= ? then 1 else 0 end) as week,
                sum(case when viewed_at >= ? then 1 else 0 end) as month',
                [$since($now->startOfDay()), $since($now->startOfWeek()), $since($now->startOfMonth())],
            )
            ->toBase()
            ->first();

        return [
            'today' => (int) ($counts->today ?? 0),
            'week' => (int) ($counts->week ?? 0),
            'month' => (int) ($counts->month ?? 0),
            'total' => (int) ($counts->total ?? 0) + $history->sum('qr_scans'),
        ];
    }

    /**
     * Adds `total_views` and `qr_scans` to a query of restaurants, as
     * sub-selects, so a page of them is one query: each row's days added up
     * in `menu_days` before its last LIST_RAW_DAYS days, plus the raw rows of
     * those. The days are the restaurant's own: the cut-off of each timezone
     * in use is worked out here, so `menu_days` and the raw rows meet at a
     * midnight and no visit is counted twice.
     *
     * @template TRestaurants of \Illuminate\Database\Eloquent\Model
     *
     * @param  Builder<TRestaurants>  $restaurants  a query of `restaurants`
     * @return Builder<TRestaurants>
     */
    public static function withTotals(Builder $restaurants): Builder
    {
        $cutOff = fn (?string $timezone): CarbonImmutable => CarbonImmutable::now(Restaurant::validTimezone($timezone))
            ->startOfDay()
            ->subDays(self::LIST_RAW_DAYS - 1);
        // A list builds its query several times a request (the rows, the count).
        $timezones = PerRequest::remember('restaurant-timezones', fn (): array => Restaurant::query()->whereNotNull('timezone')->distinct()->pluck('timezone')->all());
        [$beforeDay, $dayBindings] = self::perTimezone($timezones, fn (?string $timezone): string => $cutOff($timezone)->toDateString());
        [$fromInstant, $instantBindings] = self::perTimezone($timezones, fn (?string $timezone): string => $cutOff($timezone)->utc()->toDateTimeString());

        // The row's own columns first: a raw select alone would replace them.
        if ($restaurants->getQuery()->columns === null) {
            $restaurants->select('restaurants.*');
        }

        foreach (['total_views' => 'views', 'qr_scans' => 'qr_scans'] as $alias => $column) {
            $history = MenuDay::query()
                ->selectRaw("coalesce(sum({$column}), 0)")
                ->whereColumn('menu_days.restaurant_id', 'restaurants.id')
                ->whereRaw("menu_days.day < {$beforeDay}", $dayBindings)
                ->toBase();
            $recent = MenuSession::query()
                ->selectRaw('count(*)')
                ->whereColumn('menu_sessions.restaurant_id', 'restaurants.id')
                ->whereRaw("menu_sessions.viewed_at >= {$fromInstant}", $instantBindings)
                ->when($column === 'qr_scans', fn ($query) => $query->where('via_qr', true))
                ->toBase();

            $restaurants->selectRaw(
                "({$history->toSql()}) + ({$recent->toSql()}) as {$alias}",
                [...$history->getBindings(), ...$recent->getBindings()],
            );
        }

        return $restaurants;
    }

    /**
     * Views, distinct visitors and QR scans of some visits, the views from
     * `$since` on (today's) and the last visit, in one pass rather than
     * several counts. Without the distinct count when the caller counts
     * visitors with sketches: the costliest part, for a number unused.
     *
     * @param  HasMany<MenuSession, Restaurant>  $visits
     * @return array{views: int, unique_visitors: int, qr_scans: int, since: int, last: string|null}
     */
    public static function count(HasMany $visits, CarbonImmutable $since, bool $countVisitors = true): array
    {
        $visitors = $countVisitors ? 'count(distinct session_id)' : '0';

        $totals = $visits->toBase()
            ->selectRaw(
                "count(*) as views, {$visitors} as visitors, sum(case when via_qr then 1 else 0 end) as scans, sum(case when viewed_at >= ? then 1 else 0 end) as since, max(viewed_at) as last",
                [$since->utc()->toDateTimeString()],
            )
            ->first();

        return [
            'views' => (int) ($totals->views ?? 0),
            'unique_visitors' => (int) ($totals->visitors ?? 0),
            'qr_scans' => (int) ($totals->scans ?? 0),
            'since' => (int) ($totals->since ?? 0),
            'last' => $totals->last ?? null,
        ];
    }

    /**
     * The days added up before `$until` (MenuDays::boundary(), where the
     * raw rows take over), oldest first; none while every visit is still a
     * raw row.
     *
     * @param  array<int, string>  $columns
     * @return Collection<int, MenuDay>
     */
    public static function history(Restaurant $restaurant, CarbonImmutable $until, array $columns): Collection
    {
        return $restaurant->menuDays()->where('day', '<', $until->toDateString())->orderBy('day')->get($columns);
    }

    /**
     * Distinct visitors over raw rows and the days before `$until` together:
     * the raw ids into a sketch, merged with the days'.
     *
     * @param  iterable<string>  $rawIds
     * @param  'visitorSketch'|'cartedSketch'  $sketch
     */
    public static function visitors(Restaurant $restaurant, iterable $rawIds, CarbonImmutable $until, string $sketch): int
    {
        return HyperLogLog::of($rawIds)->merge(MenuDays::mergedSketch($restaurant, $until, $sketch))->count();
    }

    /**
     * A SQL value that differs by `restaurants.timezone`: one arm for each
     * timezone stored, and the app's for a restaurant without one.
     *
     * @param  array<int, string>  $timezones
     * @param  callable(?string): string  $valueIn
     * @return array{0: string, 1: array<int, string>}
     */
    private static function perTimezone(array $timezones, callable $valueIn): array
    {
        if ($timezones === []) {
            return ['?', [$valueIn(null)]];
        }

        $bindings = [];

        foreach ($timezones as $timezone) {
            array_push($bindings, $timezone, $valueIn($timezone));
        }

        $arms = str_repeat(' when ? then ?', count($timezones));

        return ["(case restaurants.timezone{$arms} else ? end)", [...$bindings, $valueIn(null)]];
    }
}
