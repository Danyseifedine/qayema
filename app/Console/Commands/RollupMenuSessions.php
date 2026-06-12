<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Models\RestaurantDailyStat;
use App\Models\RestaurantStatistic;
use Illuminate\Console\Command;

class RollupMenuSessions extends Command
{
    protected $signature = 'stats:rollup
                            {--days=2 : How many trailing days to (re)compute per restaurant}
                            {--prune-months=6 : Prune raw menu_sessions older than this many months}';

    protected $description = 'Roll up raw menu_sessions into restaurant_stats_daily (bucketed per restaurant timezone) and prune old raw rows';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $pruneMonths = (int) $this->option('prune-months');

        Restaurant::query()->orderBy('id')->chunkById(100, function ($restaurants) use ($days) {
            foreach ($restaurants as $restaurant) {
                $this->rollupRestaurant($restaurant, $days);
            }
        });

        if ($pruneMonths > 0) {
            $pruned = RestaurantStatistic::where('viewed_at', '<', now()->subMonths($pruneMonths))->delete();
            $this->info("Pruned {$pruned} raw menu_sessions rows older than {$pruneMonths} months.");
        }

        $this->info('Rollup complete.');

        return self::SUCCESS;
    }

    /**
     * Compute daily buckets in the restaurant's own timezone so "Tuesday"
     * means Tuesday where the restaurant lives, not UTC.
     */
    private function rollupRestaurant(Restaurant $restaurant, int $days): void
    {
        $timezone = $restaurant->timezone ?: 'UTC';

        for ($offset = 1; $offset <= $days; $offset++) {
            $localDay = now($timezone)->subDays($offset);
            $startUtc = $localDay->copy()->startOfDay()->utc();
            $endUtc = $localDay->copy()->endOfDay()->utc();

            $rows = RestaurantStatistic::where('restaurant_id', $restaurant->id)
                ->whereBetween('viewed_at', [$startUtc, $endUtc])
                ->get(['session_id', 'via_qr', 'whatsapp_orders', 'time_spent']);

            if ($rows->isEmpty()) {
                continue;
            }

            $withTime = $rows->filter(fn ($row) => ($row->time_spent ?? 0) > 0);

            RestaurantDailyStat::updateOrCreate(
                ['restaurant_id' => $restaurant->id, 'date' => $localDay->toDateString()],
                [
                    'visits' => $rows->count(),
                    'unique_sessions' => $rows->pluck('session_id')->unique()->count(),
                    'qr_visits' => $rows->where('via_qr', true)->count(),
                    'whatsapp_orders' => (int) $rows->sum('whatsapp_orders'),
                    'avg_time_spent' => $withTime->isEmpty() ? null : (int) round($withTime->avg('time_spent')),
                ],
            );
        }
    }
}
