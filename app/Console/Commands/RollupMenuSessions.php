<?php

namespace App\Console\Commands;

use App\Models\RestaurantStatistic;
use Illuminate\Console\Command;

class RollupMenuSessions extends Command
{
    protected $signature = 'stats:rollup
                            {--prune-months=6 : Prune raw menu_sessions older than this many months}';

    protected $description = 'Prune raw menu_sessions (RestaurantStatistic) rows older than the retention window';

    public function handle(): int
    {
        $pruneMonths = (int) $this->option('prune-months');

        if ($pruneMonths > 0) {
            $pruned = RestaurantStatistic::where('viewed_at', '<', now()->subMonths($pruneMonths))->delete();
            $this->info("Pruned {$pruned} raw menu_sessions rows older than {$pruneMonths} months.");
        }

        return self::SUCCESS;
    }
}
