<?php

namespace App\Services\Analytics;

use App\Enums\MenuEventType;
use App\Models\MenuDay;
use App\Models\MenuEvent;
use App\Models\MenuSession;
use App\Models\Restaurant;
use App\Support\HyperLogLog;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;
use Throwable;

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
    /** How long the raw visits and actions are kept (stats:rollup's default). */
    public const RAW_MONTHS = 6;

    /**
     * From this many days ago the analytics read raw rows; before it,
     * `menu_days`. RAW_MONTHS counted as 30-day months, so it always falls
     * inside the raw rows kept.
     */
    public const RAW_DAYS = self::RAW_MONTHS * 30;

    /** When the last addUpMissing() started, and the restaurants it could not add up. */
    public const LAST_RUN_KEY = 'menu-days:last-run';

    /**
     * How far before the last run a row can be and still belong to a day
     * that was not finished then: a local day, a clock change, and slack.
     */
    private const UNFINISHED_SECONDS = 26 * 3600;

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
     * Adds up every finished day not added up yet, for every restaurant
     * that can have one: after a first run, only those with a visit or an
     * action since the day the last run could not finish, and those it
     * could not add up. One restaurant that fails is logged and skipped,
     * never the reason the others wait; the caller keeps its raw rows.
     *
     * @return array{written: int, failed: list<int>} the days written, and the restaurants that failed
     */
    public function addUpMissing(): array
    {
        $startedAt = now()->getTimestamp();
        $written = 0;
        $failed = [];

        foreach ($this->restaurantsToAddUp(Cache::get(self::LAST_RUN_KEY)) as $restaurant) {
            try {
                $written += $this->addUpFor($restaurant);
            } catch (Throwable $exception) {
                $failed[] = $restaurant->id;
                Log::error('Menu analytics rollup failed for a restaurant: its raw rows are kept until it works.', [
                    'restaurant_id' => $restaurant->id,
                    'exception' => $exception,
                ]);
            }
        }

        Cache::forever(self::LAST_RUN_KEY, ['at' => $startedAt, 'failed' => $failed]);

        return ['written' => $written, 'failed' => $failed];
    }

    /**
     * Every restaurant on a first run (or once the cache lost the last
     * one); then those with raw rows since the last run's unfinished days
     * (the counting indexes answer it per restaurant) and the ones it failed.
     *
     * @param  array{at: int, failed: list<int>}|null  $lastRun
     * @return LazyCollection<int, Restaurant>
     */
    private function restaurantsToAddUp(?array $lastRun): LazyCollection
    {
        $restaurants = Restaurant::query();

        if ($lastRun !== null) {
            $since = CarbonImmutable::createFromTimestamp($lastRun['at'] - self::UNFINISHED_SECONDS, 'UTC');

            // Grouped, so lazyById's own `id >` applies to all of it.
            $restaurants->where(fn ($query) => $query
                ->whereIn('id', MenuSession::query()->select('restaurant_id')->where('viewed_at', '>=', $since))
                ->orWhereIn('id', MenuEvent::query()->select('restaurant_id')->where('occurred_at', '>=', $since))
                ->orWhereIn('id', $lastRun['failed']));
        }

        return $restaurants->lazyById(100);
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
        $written = 0;

        foreach ($this->daysWithActivity($restaurant, $timezone, $since) as $day) {
            $local = CarbonImmutable::parse($day, $timezone);

            // Every day from $since on is new: $since is the day after the last one added up.
            if ($local->gte($today)) {
                continue;
            }

            $this->addUp($restaurant, $local);
            $written++;
        }

        return $written;
    }

    /**
     * One day's row, from its raw visits and actions, counted by the
     * database (GROUP BY, as MenuStats counts them) rather than read row by
     * row: a busy menu's day is tens of thousands of rows. Only the
     * visitors are read one by one, as ids streamed into their sketches.
     */
    public function addUp(Restaurant $restaurant, CarbonImmutable $day): MenuDay
    {
        $timezone = $restaurant->localTimezone();
        $start = $day->setTimezone($timezone)->startOfDay();
        // The next midnight, not 24 hours on: a day a clock change shortens or lengthens.
        $range = [$start->utc(), $start->addDay()->startOfDay()->utc()];

        $visits = $restaurant->menuSessions()->toBase()
            ->where('viewed_at', '>=', $range[0])
            ->where('viewed_at', '<', $range[1]);
        $events = $restaurant->menuEvents()->toBase()
            ->where('occurred_at', '>=', $range[0])
            ->where('occurred_at', '<', $range[1]);

        $views = $qrScans = 0;
        $languages = $devices = $browsers = $systems = [];

        // One row for each mix of language, device, browser, system and QR.
        $kinds = (clone $visits)
            ->selectRaw("COALESCE(locale, 'unknown') as language, COALESCE(device_type, 'unknown') as device, COALESCE(browser, 'unknown') as agent, COALESCE(os, 'unknown') as system, via_qr, COUNT(*) as total")
            ->groupBy('language', 'device', 'agent', 'system', 'via_qr')
            ->get();

        foreach ($kinds as $kind) {
            $total = (int) $kind->total;
            $views += $total;
            $qrScans += (bool) $kind->via_qr ? $total : 0;
            $this->tally($languages, $kind->language, $total);
            $this->tally($devices, $kind->device, $total);
            $this->tally($browsers, $kind->agent, $total);
            $this->tally($systems, $kind->system, $total);
        }

        // By UTC minute, so a timezone half or three quarters of an hour off still lands each view in its own hour.
        $hours = array_fill(0, 24, 0);
        $minutes = (clone $visits)->selectRaw('SUBSTR(viewed_at, 1, 16) as bucket, COUNT(*) as total')->groupBy('bucket')->pluck('total', 'bucket');

        foreach ($minutes as $minute => $total) {
            $hours[CarbonImmutable::createFromFormat('Y-m-d H:i', (string) $minute, 'UTC')->setTimezone($timezone)->hour] += (int) $total;
        }

        $actions = $dishes = $categories = $searches = $missed = [];
        $done = (clone $events)
            ->selectRaw('type, dish_id, category_id, value, COUNT(*) as total')
            ->groupBy('type', 'dish_id', 'category_id', 'value')
            ->get();

        foreach ($done as $event) {
            $total = (int) $event->total;
            $this->tally($actions, $event->type, $total);

            if ($event->type === MenuEventType::DishAdd->value && $event->dish_id !== null) {
                $this->tally($dishes, (string) $event->dish_id, $total);
            }

            if ($event->type === MenuEventType::CategoryOpen->value && $event->category_id !== null) {
                $this->tally($categories, (string) $event->category_id, $total);
            }

            if (in_array($event->type, [MenuEventType::Search->value, MenuEventType::SearchMiss->value], true) && $event->value !== null) {
                $this->tally($searches, $event->value, $total);
                if ($event->type === MenuEventType::SearchMiss->value) {
                    $this->tally($missed, $event->value, $total);
                }
            }
        }

        return $restaurant->menuDays()->create([
            'day' => $start->toDateString(),
            'views' => $views,
            'qr_scans' => $qrScans,
            'visitors' => $this->sketch(clone $visits)->toStored(),
            'carted' => $this->sketch((clone $events)->where('type', MenuEventType::DishAdd->value))->toStored(),
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

    /** The distinct guests of $rows, streamed into a sketch rather than loaded. */
    private function sketch(Builder $rows): HyperLogLog
    {
        $sketch = new HyperLogLog;

        foreach ($rows->distinct()->select('session_id')->cursor() as $row) {
            $sketch->add((string) $row->session_id);
        }

        return $sketch;
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
    private function tally(array &$counts, string $key, int $times): void
    {
        $counts[$key] = ($counts[$key] ?? 0) + $times;
    }
}
