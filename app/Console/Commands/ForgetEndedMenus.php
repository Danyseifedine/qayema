<?php

namespace App\Console\Commands;

use App\Models\FeatureGrant;
use App\Models\Restaurant;
use App\Services\Menu\MenuCache;
use Illuminate\Console\Command;

/**
 * A package or a grant that starts or ends with the clock saves nothing, so
 * nothing clears the menus Cloudflare keeps (MenuCache). Run every hour, this
 * clears the menus whose package started or ended, or whose grant ended,
 * since the last run, so guests see the change within the hour.
 */
class ForgetEndedMenus extends Command
{
    protected $signature = 'menus:forget-ended
                            {--minutes=65 : Look back this far (a little over the hour between runs)}';

    protected $description = 'Clear the cached menus whose package or grant started or ended in the last hour';

    public function handle(MenuCache $menus): int
    {
        $since = now()->subMinutes(max(1, (int) $this->option('minutes')));
        $window = [$since, now()];

        $restaurants = Restaurant::query()
            ->where(fn ($query) => $query->whereBetween('package_ends_at', $window)->orWhereBetween('package_started_at', $window))
            ->pluck('id')
            ->merge(FeatureGrant::query()->whereBetween('ends_at', $window)->pluck('restaurant_id'))
            ->unique();

        foreach ($restaurants as $restaurant) {
            $menus->forget($restaurant);
        }

        $this->info("Clearing {$restaurants->count()} menus whose package or grant changed since {$since->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
