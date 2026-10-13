<?php

namespace App\Services\Analytics;

use App\Enums\MenuEventType;
use App\Models\MenuDay;
use App\Models\Restaurant;
use App\Support\HyperLogLog;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Adds up a menu's raw visits and guest actions into one `menu_days` row per
 * day of its own (its timezone), so the history outlives the raw rows, which
 * `stats:rollup` deletes after RAW_MONTHS. Every count the analytics show is
 * kept, plus devices, browsers and systems; visitors as HyperLogLog sketches.
 *
 * A finished day is added up once and never again: by the time its raw rows
 * go, its row has long been written. The analytics read the rows for the days
 * before boundary() and the raw rows from then on (MenuStats), so nothing is
 * counted twice.
 */
class MenuDays
{
    /** How long the raw visits and actions are kept (stats:rollup). */
    public const RAW_MONTHS = 6;

    /** From this many days ago the analytics read raw rows; before it, `menu_days`. Inside RAW_MONTHS. */
    public const RAW_DAYS = 180;

    /** The first of the restaurant's days the analytics take from raw rows. */
    public static function boundary(Restaurant $restaurant): CarbonImmutable
    {
        return CarbonImmutable::now($restaurant->localTimezone())->startOfDay()->subDays(self::RAW_DAYS);
    }

    /**
     * The visitors (or carted visitors) of every day before $until, merged
     * into one sketch. Past days never change, so the merge is kept until
     * the restaurant's midnight, when the boundary moves: the analytics and
     * the admin pay for merging a year of days once a day, not on every view.
     *
     * @param  'visitorSketch'|'cartedSketch'  $sketch
     */
    public static function mergedSketch(Restaurant $restaurant, CarbonImmutable $until, string $sketch): HyperLogLog
    {
        $key = "menu-days:{$restaurant->id}:{$sketch}:{$until->toDateString()}";
        $column = $sketch === 'visitorSketch' ? 'visitors' : 'carted';
        $tomorrow = CarbonImmutable::now($restaurant->localTimezone())->startOfDay()->addDay();

        $stored = Cache::remember($key, $tomorrow, function () use ($restaurant, $until, $column, $sketch): string {
            $merged = new HyperLogLog;
            $restaurant->menuDays()
                ->where('day', '<', $until->toDateString())
                ->select(['id', $column])
                ->lazyById(200)
                ->each(fn (MenuDay $day) => $merged->merge($day->{$sketch}()));

            // As text: the database cache store keeps text, not bytes.
            return base64_encode($merged->toStored());
        });

        return HyperLogLog::fromStored(base64_decode($stored));
    }

    /**
     * Adds up every finished day not added up yet, for every restaurant.
     *
     * @return int the days written
     */
    public function addUpMissing(): int
    {
        $written = 0;

        Restaurant::query()->chunkById(100, function ($restaurants) use (&$written): void {
            foreach ($restaurants as $restaurant) {
                $written += $this->addUpFor($restaurant);
            }
        });

        return $written;
    }

    /** @return int the days written for this restaurant */
    public function addUpFor(Restaurant $restaurant): int
    {
        $timezone = $restaurant->localTimezone();
        $today = CarbonImmutable::now($timezone)->startOfDay();
        // Only the raw rows after the last day added up are looked at: a
        // night's run reads one day of rows, not the six months kept.
        $last = $restaurant->menuDays()->max('day');
        $since = $last === null ? null : CarbonImmutable::parse($last, $timezone)->startOfDay()->addDay();
        $added = $restaurant->menuDays()
            ->when($since, fn ($query, CarbonImmutable $since) => $query->where('day', '>=', $since->toDateString()))
            ->pluck('day')
            ->map(fn ($day): string => $day->toDateString())
            ->flip();
        $written = 0;

        foreach ($this->daysWithActivity($restaurant, $timezone, $since) as $day) {
            $local = CarbonImmutable::parse($day, $timezone);

            if ($local->gte($today) || isset($added[$day])) {
                continue;
            }

            $this->addUp($restaurant, $local);
            $written++;
        }

        return $written;
    }

    /** One day's row, from its raw visits and actions. */
    public function addUp(Restaurant $restaurant, CarbonImmutable $day): MenuDay
    {
        $timezone = $restaurant->localTimezone();
        $start = $day->setTimezone($timezone)->startOfDay();
        // The next midnight, not 24 hours on: a day a clock change shortens or lengthens.
        $range = [$start->utc(), $start->addDay()->startOfDay()->utc()];

        $visits = $restaurant->menuSessions()
            ->where('viewed_at', '>=', $range[0])
            ->where('viewed_at', '<', $range[1])
            ->get(['session_id', 'device_type', 'browser', 'os', 'locale', 'viewed_at', 'via_qr']);

        $hours = array_fill(0, 24, 0);
        $languages = $devices = $browsers = $systems = [];

        foreach ($visits as $visit) {
            $hours[CarbonImmutable::instance($visit->viewed_at)->setTimezone($timezone)->hour]++;
            $this->tally($languages, $visit->locale);
            $this->tally($devices, $visit->device_type);
            $this->tally($browsers, $visit->browser);
            $this->tally($systems, $visit->os);
        }

        $events = $restaurant->menuEvents()
            ->where('occurred_at', '>=', $range[0])
            ->where('occurred_at', '<', $range[1])
            ->get(['session_id', 'type', 'dish_id', 'category_id', 'value']);

        $actions = $dishes = $categories = $searches = $missed = [];
        $carted = new HyperLogLog;

        foreach ($events as $event) {
            $this->tally($actions, $event->type->value);

            if ($event->type === MenuEventType::DishAdd) {
                $carted->add($event->session_id);
                if ($event->dish_id !== null) {
                    $this->tally($dishes, (string) $event->dish_id);
                }
            }

            if ($event->type === MenuEventType::CategoryOpen && $event->category_id !== null) {
                $this->tally($categories, (string) $event->category_id);
            }

            if (in_array($event->type, [MenuEventType::Search, MenuEventType::SearchMiss], true) && $event->value !== null) {
                $this->tally($searches, $event->value);
                if ($event->type === MenuEventType::SearchMiss) {
                    $this->tally($missed, $event->value);
                }
            }
        }

        return $restaurant->menuDays()->create([
            'day' => $start->toDateString(),
            'views' => $visits->count(),
            'qr_scans' => $visits->where('via_qr', true)->count(),
            'visitors' => HyperLogLog::of($visits->pluck('session_id'))->toStored(),
            'carted' => $carted->toStored(),
            'hours' => $hours,
            'languages' => $languages,
            'devices' => $devices,
            'browsers' => $browsers,
            'systems' => $systems,
            'actions' => $actions,
            'dishes_added' => $dishes,
            'categories_opened' => $categories,
            'searches' => $searches,
            'missed_searches' => $missed,
        ]);
    }

    /**
     * The restaurant's days that had a visit or an action, oldest first,
     * from the raw rows (from $since on) grouped by UTC hour (portable, at
     * most 24 a day).
     *
     * @return array<int, string> local dates
     */
    private function daysWithActivity(Restaurant $restaurant, string $timezone, ?CarbonImmutable $since): array
    {
        $from = $since?->utc();
        $buckets = $restaurant->menuSessions()
            ->when($from, fn ($query, CarbonImmutable $from) => $query->where('viewed_at', '>=', $from))
            ->selectRaw('SUBSTR(viewed_at, 1, 13) as bucket')->distinct()->pluck('bucket')
            ->merge($restaurant->menuEvents()
                ->when($from, fn ($query, CarbonImmutable $from) => $query->where('occurred_at', '>=', $from))
                ->selectRaw('SUBSTR(occurred_at, 1, 13) as bucket')->distinct()->pluck('bucket'));

        return $buckets
            ->map(fn (string $bucket): string => CarbonImmutable::createFromFormat('Y-m-d H', $bucket, 'UTC')->setTimezone($timezone)->toDateString())
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /** @param  array<string, int>  $counts */
    private function tally(array &$counts, ?string $key): void
    {
        $key ??= 'unknown';
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
}
