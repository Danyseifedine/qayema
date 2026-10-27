<?php

namespace App\Console\Commands;

use App\Services\Menu\MenuCache;
use App\Services\Menu\PendingPurges;
use Illuminate\Console\Command;

/**
 * Sends the Cloudflare purges it refused (PendingPurges), every minute, in
 * at most the five calls a minute its Free plan allows; what is still
 * refused waits for the next minute.
 */
class PurgePendingMenus extends Command
{
    protected $signature = 'menus:purge-pending';

    protected $description = 'Send the cached-menu clearings Cloudflare refused earlier';

    public function handle(): int
    {
        if (! MenuCache::isOn() || PendingPurges::all() === []) {
            return self::SUCCESS;
        }

        $cleared = PendingPurges::send();
        $this->info("Cleared {$cleared} pending menu address(es).");

        return self::SUCCESS;
    }
}
