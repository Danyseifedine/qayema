<?php

namespace App\Console\Commands;

use App\Mail\PosUnreachable;
use App\Models\PosConnection;
use App\Services\Push\AdminAlerts;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Every fifteen minutes: a connected menu whose POS has not answered for
 * over an hour is told to its owner (by email, when the account has one)
 * and to the admins' phones, once per outage. The next pull that works
 * clears it (SyncPosCatalog), so a new outage is told again.
 */
class WatchPosConnections extends Command
{
    protected $signature = 'pos:watch';

    protected $description = 'Tell owners and admins about POS links that stopped answering an hour ago or more';

    public function handle(AdminAlerts $alerts): int
    {
        $told = 0;

        PosConnection::query()
            ->whereNotNull('last_error')
            ->whereNull('alerted_at')
            ->with('restaurant.user')
            ->chunkById(100, function ($connections) use ($alerts, &$told): void {
                foreach ($connections as $connection) {
                    $restaurant = $connection->restaurant;

                    if ($connection->unreachableSince() === null || ! $restaurant->isConnectedToPos()) {
                        continue;
                    }

                    $connection->forceFill(['alerted_at' => now()])->save();

                    if (filled($restaurant->user?->email)) {
                        Mail::to($restaurant->user->email)->send(new PosUnreachable($restaurant, $connection));
                    }

                    $alerts->posUnreachable($restaurant);
                    $told++;
                }
            });

        $this->info("Told {$told} outages.");

        return self::SUCCESS;
    }
}
