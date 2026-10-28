<?php

namespace App\Services\Orders;

use RuntimeException;

/**
 * The cart costs more than the guest was shown: an offer ended, or a price
 * went up, while it sat on their phone (or the phone's clock is wrong). It
 * is not placed; the guest is shown the prices as they are and sends again.
 */
class PricesChanged extends RuntimeException
{
    /**
     * @param  array<int, string>  $prices  dish id => its own price now, before any choice or discount
     * @param  array<int, array{offer: int, type: string, value: float}|null>  $deals  dish id => the deal the server gives it now
     */
    public function __construct(public readonly array $prices, public readonly array $deals = [])
    {
        parent::__construct('The cart costs more than the guest was shown.');
    }
}
