<?php

namespace App\Services\Orders;

use App\Enums\Fulfilment;
use App\Enums\OrderStatus;
use App\Models\Restaurant;
use Illuminate\Support\Carbon;

/**
 * Where a restaurant's orders placed in the menu stand, in the numbers the
 * dashboard watches: how many wait to be accepted (orders to a table apart,
 * since they have a page of their own), the newest one's id (a new one is a
 * new order) and when a guest last changed one (a new time is a change to
 * read). Sent live (OrdersChanged) and asked for when the live
 * connection is down (GET /api/orders/pulse).
 */
final class OrderPulse
{
    /**
     * @return array{open: int, table_open: int, latest: int|null, changed: string|null}
     */
    public static function for(Restaurant $restaurant): array
    {
        // One pass: asked on every new or changed order, and once a minute
        // by a dashboard that cannot hear Pusher.
        $pulse = $restaurant->orders()->inMenu()->reorder()
            ->selectRaw(
                'max(id) as latest, max(guest_updated_at) as changed,'
                .' sum(case when status = ? and (fulfilment is null or fulfilment != ?) then 1 else 0 end) as open,'
                .' sum(case when status = ? and fulfilment = ? then 1 else 0 end) as table_open',
                [OrderStatus::Placed->value, Fulfilment::DineIn->value, OrderStatus::Placed->value, Fulfilment::DineIn->value],
            )
            ->toBase()
            ->first();

        return [
            'open' => (int) ($pulse->open ?? 0),
            'table_open' => (int) ($pulse->table_open ?? 0),
            'latest' => $pulse?->latest === null ? null : (int) $pulse->latest,
            'changed' => $pulse?->changed === null ? null : Carbon::parse($pulse->changed)->toIso8601String(),
        ];
    }
}
