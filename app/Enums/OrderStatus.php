<?php

namespace App\Enums;

/**
 * Where an order has got to.
 *
 * Deliberately three states. An order arrives, it is dealt with, or it is
 * called off. Anything finer (accepted, preparing, ready) is a kitchen
 * workflow this product does not run.
 */
enum OrderStatus: string
{
    case Placed = 'placed';
    case Done = 'done';
    case Cancelled = 'cancelled';
}
