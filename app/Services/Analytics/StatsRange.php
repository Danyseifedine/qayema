<?php

namespace App\Services\Analytics;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Carbon\CarbonImmutable;

/**
 * One restaurant's analytics range, worked out once and shared by MenuStats
 * and MenuOrderStats: its days in the restaurant's own timezone, where the
 * range starts and, for "All time", where the days added up in `menu_days`
 * hand over to the raw rows.
 */
final readonly class StatsRange
{
    /**
     * @param  string  $key  a MenuStats::RANGES key
     * @param  CarbonImmutable  $today  the start of today, in the restaurant's timezone
     * @param  CarbonImmutable|null  $from  the start of the range, in the restaurant's timezone; null for "all"
     * @param  CarbonImmutable|null  $historyUntil  for "all" with days added up before MenuDays::boundary(): the boundary, where the raw rows take over from `menu_days`; null otherwise
     */
    public function __construct(
        public Restaurant $restaurant,
        public string $key,
        public string $timezone,
        public CarbonImmutable $today,
        public ?CarbonImmutable $from,
        public ?CarbonImmutable $historyUntil,
    ) {}

    public static function of(Restaurant $restaurant, string $key): self
    {
        $timezone = $restaurant->localTimezone();
        $today = CarbonImmutable::now($timezone)->startOfDay();

        $days = MenuStats::RANGES[$key] ?? null;
        $from = $days === null ? null : $today->subDays($days - 1);

        $boundary = MenuDays::boundary($restaurant);
        $historyUntil = $from === null
            && $restaurant->menuDays()->where('day', '<', $boundary->toDateString())->exists()
            ? $boundary
            : null;

        return new self($restaurant, $key, $timezone, $today, $from, $historyUntil);
    }

    /**
     * The period before this one, for "up 12%": as many days before its
     * start; for today, yesterday up to this time of day, so a morning is not
     * set against a whole day. Only for a range with a start.
     *
     * @return array{0: CarbonImmutable, 1: CarbonImmutable}
     */
    public function previousPeriod(): array
    {
        $days = (int) MenuStats::RANGES[$this->key];
        $start = $this->from->subDays($days);

        return [$start, $this->key === 'today' ? CarbonImmutable::now($this->timezone)->subDay() : $this->from];
    }

    /**
     * Current names, in the owner's language, for dishes or categories.
     *
     * @param  class-string<Dish|Category>  $model
     * @param  iterable<int|null>  $ids
     * @return array<int, string>
     */
    public function names(string $model, iterable $ids): array
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
