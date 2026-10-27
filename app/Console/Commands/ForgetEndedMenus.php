<?php

namespace App\Console\Commands;

use App\Models\FeatureGrant;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Services\Menu\MenuCache;
use Illuminate\Console\Command;

/**
 * A package or a grant that starts or ends with the clock saves nothing, so
 * nothing clears the menus Cloudflare keeps (MenuCache). Run every hour, this
 * clears the menus whose package started, whose menu went offline (the
 * grace days past an ended package, Restaurant::menuOnline()), or whose
 * grant ended, since the last run, so guests see the change within the hour.
 * The end of a package itself changes nothing a guest sees.
 *
 * An offer that started or ended is cleared too: the guest's phone shows
 * and hides offers on time by itself (menu-offers.js), but the page as
 * drawn (what a search engine reads, a phone without scripts) and the
 * prices it starts from should not lag behind. Repeats inside the dates
 * are left to the phone.
 */
class ForgetEndedMenus extends Command
{
    protected $signature = 'menus:forget-ended
                            {--minutes=65 : Look back this far (a little over the hour between runs)}';

    protected $description = 'Clear the cached menus whose package started, menu went offline or grant ended in the last hour';

    public function handle(MenuCache $menus): int
    {
        $since = now()->toImmutable()->subMinutes(max(1, (int) $this->option('minutes')));
        $window = [$since, now()->toImmutable()];
        $wentOffline = [$since->subDays(Restaurant::graceDays()), $window[1]->subDays(Restaurant::graceDays())];

        $restaurants = Restaurant::query()
            ->where(fn ($query) => $query->whereBetween('package_ends_at', $wentOffline)->orWhereBetween('package_started_at', $window))
            ->pluck('id')
            ->merge(FeatureGrant::query()->whereBetween('ends_at', $window)->pluck('restaurant_id'))
            ->merge(Offer::query()->where(fn ($query) => $query->whereBetween('starts_at', $window)->orWhereBetween('ends_at', $window))->pluck('restaurant_id'))
            ->unique();

        foreach ($restaurants as $restaurant) {
            $menus->forget($restaurant);
        }

        $this->info("Clearing {$restaurants->count()} menus whose package, grant, offer or being online changed since {$since->toDateTimeString()}.");

        return self::SUCCESS;
    }
}
