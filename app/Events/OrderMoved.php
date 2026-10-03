<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

/**
 * An order placed in the menu moved on (accepted, on its way, done,
 * cancelled, or changed): the guest following it sees it at once. The
 * channel is named after the order's tracking token, a long random string,
 * so only someone with the order's link can listen.
 */
class OrderMoved implements ShouldBroadcastNow
{
    public function __construct(public readonly Order $order) {}

    public function broadcastOn(): Channel
    {
        return new Channel('order.'.$this->order->tracking_token);
    }

    public function broadcastAs(): string
    {
        return 'order.moved';
    }

    /** Only an order someone can follow. */
    public function broadcastWhen(): bool
    {
        return $this->order->tracking_token !== null;
    }

    /** @return array<string, mixed> */
    public function broadcastWith(): array
    {
        return ['status' => $this->order->status->value];
    }
}
