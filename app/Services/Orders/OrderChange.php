<?php

namespace App\Services\Orders;

use App\Models\Order;

/**
 * A guest's change to their order (OrderPlacer::change()): the order as it
 * now stands, and the dishes the change went without because the menu no
 * longer sells them, named in the guest's language.
 */
final readonly class OrderChange
{
    /**
     * @param  list<string>  $unavailable
     */
    public function __construct(
        public Order $order,
        public array $unavailable,
    ) {}
}
