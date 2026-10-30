<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Restaurant;
use Illuminate\Validation\ValidationException;

/**
 * While a menu is connected to a POS, the POS makes its categories and
 * dishes: adding, deleting and changing what the POS sends is refused here
 * (the order of things and their icons stay the owner's).
 */
trait GuardsPosMenu
{
    protected function refuseWhilePosConnected(Restaurant $restaurant): void
    {
        if ($restaurant->isConnectedToPos()) {
            throw ValidationException::withMessages(['pos' => __('Managed in your POS.')]);
        }
    }
}
