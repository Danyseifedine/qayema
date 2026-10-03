<?php

namespace App\Services\Orders;

use App\Events\OrderMoved;
use App\Events\OrdersChanged;
use App\Models\Order;

use function Illuminate\Support\defer;

/**
 * Telling the people watching an order placed in the menu: the owner's
 * dashboard, and the guest following it. Sent after the response has gone
 * (defer), so neither the guest placing an order nor the owner tapping a
 * button waits on Pusher; and a Pusher that is down is reported, never
 * thrown: the pages check once a minute while they cannot hear it.
 */
final class OrderNews
{
    /** A new order, or a guest's change to one. */
    public static function fromGuest(Order $order): void
    {
        self::send($order, owner: true, guest: false);
    }

    /** The owner moved it on: the guest hears it, other dashboard tabs too. */
    public static function fromOwner(Order $order): void
    {
        self::send($order, owner: true, guest: true);
    }

    private static function send(Order $order, bool $owner, bool $guest): void
    {
        if ($order->tracking_token === null) {
            return;
        }

        defer(function () use ($order, $owner, $guest): void {
            if ($owner) {
                rescue(fn () => event(new OrdersChanged($order->restaurant)));
            }
            if ($guest) {
                rescue(fn () => event(new OrderMoved($order)));
            }
        });
    }
}
