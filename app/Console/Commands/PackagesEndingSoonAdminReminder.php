<?php

namespace App\Console\Commands;

use App\Models\Restaurant;
use App\Services\Push\AdminAlerts;
use Illuminate\Console\Command;

/**
 * Each morning, the admins' phones list the packages that end in the next
 * few days, so a renewal is asked for before the menu falls back to Free.
 */
class PackagesEndingSoonAdminReminder extends Command
{
    protected $signature = 'packages:ending-soon-admin-reminder
                            {--days=3 : Remind about packages that end within this many days}';

    protected $description = "Tell the admins' phones which packages end soon";

    public function handle(AdminAlerts $alerts): int
    {
        $days = max(1, (int) $this->option('days'));

        $ending = Restaurant::query()
            ->packageEndingWithin($days)
            ->orderBy('package_ends_at')
            // The main language too: a name written only in Arabic reads in it.
            ->get(['id', 'name', 'main_locale', 'package_ends_at']);

        $reached = $alerts->packagesEnding($ending);

        $this->info("{$ending->count()} packages end within {$days} days; told {$reached} phones.");

        return self::SUCCESS;
    }
}
