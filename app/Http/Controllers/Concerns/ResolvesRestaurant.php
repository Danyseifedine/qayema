<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Restaurant;
use Illuminate\Http\Request;

/**
 * Every owner endpoint works on the signed-in user's own restaurant, and 403s
 * when they have none yet.
 */
trait ResolvesRestaurant
{
    protected function restaurant(Request $request, ?string $message = null): Restaurant
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403, $message ?? '');

        return $restaurant;
    }
}
