<?php

namespace App\Services\Orders;

use App\Enums\OrderStatus;
use App\Models\Restaurant;
use Illuminate\Support\Carbon;

/**
 * Where a restaurant's orders placed in the menu stand, in three numbers the
 * dashboard watches: how many wait to be accepted, the newest one's id (a
 * new one is a new order) and when a guest last changed one (a new time is a
 * change to read). Sent live (OrdersChanged) and asked for when the live
 * connection is down (GET /api/orders/pulse).
 */
final class OrderPulse
{
    /**
     * @return array{open: int, latest: int|null, changed: string|null}
     */
    public static function for(Restaurant $restaurant): array
    {
        $orders = $restaurant->orders()->inMenu()->reorder();
        $latest = (clone $orders)->max('id');
        $changed = (clone $orders)->max('guest_updated_at');

        return [
            'open' => (clone $orders)->where('status', OrderStatus::Placed)->count(),
            'latest' => $latest === null ? null : (int) $latest,
            'changed' => $changed === null ? null : Carbon::parse($changed)->toIso8601String(),
        ];
    }
}
