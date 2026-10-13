<?php

namespace App\Console\Commands;

use App\Models\MenuEvent;
use App\Models\MenuSession;
use App\Services\Analytics\MenuDays;
use Illuminate\Console\Command;

/**
 * Nightly: every finished day of every menu is added up into `menu_days`
 * (MenuDays), then the raw visits and guest actions older than the window
 * are deleted. The history stays (the analytics read `menu_days` for old
 * days); only the per-visit rows go. If adding up fails, nothing is deleted.
 */
class RollupMenuSessions extends Command
{
    protected $signature = 'stats:rollup
                            {--prune-months=6 : Delete raw menu_sessions and menu_events older than this many months, once added up (0 keeps them)}';

    protected $description = 'Add up each finished day of menu analytics, then delete the raw rows past the window';

    public function handle(MenuDays $days): int
    {
        // First, and the whole of it: an exception here ends the command
        // before anything is deleted.
        $written = $days->addUpMissing();
        $this->info("Added up {$written} days.");

        $pruneMonths = (int) $this->option('prune-months');

        if ($pruneMonths > 0) {
            $pruned = MenuSession::where('viewed_at', '<', now()->subMonths($pruneMonths))->delete();
            $this->info("Pruned {$pruned} raw menu_sessions rows older than {$pruneMonths} months.");

            $events = MenuEvent::where('occurred_at', '<', now()->subMonths($pruneMonths))->delete();
            $this->info("Pruned {$events} menu_events rows older than {$pruneMonths} months.");
        }

        return self::SUCCESS;
    }
}
