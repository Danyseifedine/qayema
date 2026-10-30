<?php

namespace App\Console\Commands;

use App\Jobs\SyncPosCatalog;
use App\Models\PosConnection;
use Illuminate\Console\Command;

/**
 * Every hour, each connected menu pulls its POS catalog again (one job a
 * restaurant), so a ping that never arrived is made good within the hour.
 */
class SyncPosMenus extends Command
{
    protected $signature = 'pos:sync';

    protected $description = 'Queue a catalog pull for every menu connected to a POS';

    public function handle(): int
    {
        $count = 0;

        PosConnection::query()->select(['id', 'restaurant_id'])->chunkById(200, function ($connections) use (&$count): void {
            foreach ($connections as $connection) {
                SyncPosCatalog::dispatch($connection->restaurant_id);
                $count++;
            }
        });

        $this->info("Queued {$count} catalog pulls.");

        return self::SUCCESS;
    }
}
