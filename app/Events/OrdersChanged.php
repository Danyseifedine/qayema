<?php

namespace App\Events;

use App\Models\Restaurant;
use App\Services\Orders\OrderPulse;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * Something happened to a restaurant's orders (one placed, changed by a
 * guest, moved on by the owner): the dashboard gets the new pulse at once,
 * on a channel only that restaurant's owner may join (routes/channels.php).
 * Sent straight away, not queued: shared hosting runs no queue worker.
 */
class OrdersChanged implements ShouldBroadcastNow
{
    public function __construct(public readonly Restaurant $restaurant) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('orders.'.$this->restaurant->id);
    }

    public function broadcastAs(): string
    {
        return 'orders.changed';
    }

    /** @return array{open: int, latest: int|null, changed: string|null} */
    public function broadcastWith(): array
    {
        return OrderPulse::for($this->restaurant);
    }
}
