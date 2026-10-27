<?php

namespace App\Console\Commands;

use App\Models\MenuEvent;
use App\Models\MenuSession;
use App\Services\Analytics\MenuDays;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Nightly: every finished day of every menu is added up into `menu_days`
 * (MenuDays), then the raw visits and guest actions older than the window
 * are deleted, a batch at a time so no one statement holds the tables for
 * millions of rows. The history stays (the analytics read `menu_days` for
 * old days); only the per-visit rows go. A restaurant that could not be
 * added up keeps its raw rows; if adding up fails as a whole, nothing is
 * deleted.
 */
class RollupMenuSessions extends Command
{
    protected $signature = 'stats:rollup
                            {--prune-months='.MenuDays::RAW_MONTHS.' : Delete raw menu_sessions and menu_events older than this many months, once added up (0 keeps them)}
                            {--batch=10000 : Rows deleted per statement}';

    protected $description = 'Add up each finished day of menu analytics, then delete the raw rows past the window';

    public function handle(MenuDays $days): int
    {
        // First, and the whole of it: an exception here ends the command
        // before anything is deleted.
        ['written' => $written, 'failed' => $failed] = $days->addUpMissing();
        $this->info("Added up {$written} days.");

        if ($failed !== []) {
            $this->warn('Could not add up restaurant(s) '.implode(', ', $failed).'; their raw rows are kept.');
        }

        $pruneMonths = (int) $this->option('prune-months');

        if ($pruneMonths > 0) {
            $before = now()->subMonths($pruneMonths);

            $pruned = $this->prune(MenuSession::query()->where('viewed_at', '<', $before), $failed);
            $this->info("Pruned {$pruned} raw menu_sessions rows older than {$pruneMonths} months.");

            $events = $this->prune(MenuEvent::query()->where('occurred_at', '<', $before), $failed);
            $this->info("Pruned {$events} menu_events rows older than {$pruneMonths} months.");
        }

        return self::SUCCESS;
    }

    /**
     * Deletes what $rows names a batch at a time, but for the restaurants
     * not added up.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $rows
     * @param  list<int>  $kept
     */
    private function prune(Builder $rows, array $kept): int
    {
        $batch = max(1, (int) $this->option('batch'));
        $total = 0;

        do {
            $deleted = (clone $rows)->whereNotIn('restaurant_id', $kept)->limit($batch)->delete();
            $total += $deleted;
        } while ($deleted > 0);

        return $total;
    }
}
