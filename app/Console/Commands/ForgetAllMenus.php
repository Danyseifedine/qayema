<?php

namespace App\Console\Commands;

use App\Services\Menu\MenuCache;
use Illuminate\Console\Command;

/**
 * Clears every menu Cloudflare keeps (MenuCache::forgetAll()). deploy.sh runs
 * it when a release changes the menu page or its scripts, so that is what
 * guests get next, not a copy kept from the release before.
 */
class ForgetAllMenus extends Command
{
    protected $signature = 'menus:forget-all';

    protected $description = 'Clear every cached menu page from Cloudflare';

    public function handle(MenuCache $menus): int
    {
        if (! MenuCache::isOn()) {
            $this->warn('Cloudflare is not set up (services.cloudflare): nothing to clear.');

            return self::SUCCESS;
        }

        $menus->forgetAll();
        $this->info('Clearing every cached menu.');

        return self::SUCCESS;
    }
}
