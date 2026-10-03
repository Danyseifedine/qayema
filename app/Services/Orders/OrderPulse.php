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
        // One pass: asked on every new or changed order, and once a minute
        // by a dashboard that cannot hear Pusher.
        $pulse = $restaurant->orders()->inMenu()->reorder()
            ->selectRaw(
                'max(id) as latest, max(guest_updated_at) as changed, sum(case when status = ? then 1 else 0 end) as open',
                [OrderStatus::Placed->value],
            )
            ->toBase()
            ->first();

        return [
            'open' => (int) ($pulse->open ?? 0),
            'latest' => $pulse?->latest === null ? null : (int) $pulse->latest,
            'changed' => $pulse?->changed === null ? null : Carbon::parse($pulse->changed)->toIso8601String(),
        ];
    }
}
