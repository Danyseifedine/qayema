<?php

namespace App\Enums;

/**
 * Where an order has got to.
 *
 * It arrives (placed), the restaurant takes it on (accepted, which tells a
 * guest following it that a person saw it), it leaves the kitchen (ready: on
 * its way for a delivery, ready to collect for a pickup), and it is handed
 * over (done) or called off (cancelled). Anything finer (preparing, plating)
 * is a kitchen workflow this product does not run. Every step but the first
 * may be skipped.
 * A WhatsApp order is never accepted; it goes straight to done or cancelled.
 */
enum OrderStatus: string
{
    case Placed = 'placed';
    case Accepted = 'accepted';
    case Ready = 'ready';
    case Done = 'done';
    case Cancelled = 'cancelled';

    /**
     * The guest may still change it (or add to it): only until the
     * restaurant accepts it. From then on the kitchen may be at it, so a
     * change goes through a phone call.
     */
    public function isOpenToGuest(): bool
    {
        return $this === self::Placed;
    }

    /**
     * Where the restaurant may take it from here: on, never back (a step may
     * be skipped), or called off while it is still going. A tab showing an
     * old state can then neither reopen a finished order nor walk one back.
     */
    public function canMoveTo(self $next): bool
    {
        if ($this->isClosed()) {
            return false;
        }

        return $next === self::Cancelled || $next->step() > $this->step();
    }

    private function step(): int
    {
        return match ($this) {
            self::Placed => 0,
            self::Accepted => 1,
            self::Ready => 2,
            self::Done, self::Cancelled => 3,
        };
    }

    /** Nothing more will happen to it. */
    public function isClosed(): bool
    {
        return $this === self::Done || $this === self::Cancelled;
    }
}
