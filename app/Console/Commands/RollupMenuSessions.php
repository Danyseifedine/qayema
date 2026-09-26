<?php

namespace App\Console\Commands;

use App\Models\MenuEvent;
use App\Models\RestaurantStatistic;
use Illuminate\Console\Command;

class RollupMenuSessions extends Command
{
    protected $signature = 'stats:rollup
                            {--prune-months=6 : Prune raw menu_sessions and menu_events older than this many months}';

    protected $description = 'Prune raw menu_sessions and menu_events rows older than the retention window';

    public function handle(): int
    {
        $pruneMonths = (int) $this->option('prune-months');

        if ($pruneMonths > 0) {
            $pruned = RestaurantStatistic::where('viewed_at', '<', now()->subMonths($pruneMonths))->delete();
            $this->info("Pruned {$pruned} raw menu_sessions rows older than {$pruneMonths} months.");

            $events = MenuEvent::where('occurred_at', '<', now()->subMonths($pruneMonths))->delete();
            $this->info("Pruned {$events} menu_events rows older than {$pruneMonths} months.");
        }

        return self::SUCCESS;
    }
}
