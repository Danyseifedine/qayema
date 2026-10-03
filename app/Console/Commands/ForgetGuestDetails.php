<?php

namespace App\Console\Commands;

use App\Models\Order;
use Illuminate\Console\Command;

/**
 * A guest's name, phone and address are kept only while the restaurant could need
 * them. The order itself (lines, total, status) stays for the stats.
 */
class ForgetGuestDetails extends Command
{
    protected $signature = 'orders:forget-guests
                            {--days=90 : Clear guest names, phone numbers and addresses from orders older than this many days}';

    protected $description = 'Clear guest names, phone numbers, addresses and locations from old orders';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));

        $cleared = Order::query()
            ->where('placed_at', '<', now()->subDays($days))
            ->where(fn ($query) => $query->whereNotNull('guest_name')->orWhereNotNull('guest_phone')->orWhereNotNull('address')->orWhereNotNull('latitude'))
            ->update(['guest_name' => null, 'guest_phone' => null, 'address' => null, 'latitude' => null, 'longitude' => null]);

        $this->info("Cleared guest details from {$cleared} orders older than {$days} days.");

        return self::SUCCESS;
    }
}
