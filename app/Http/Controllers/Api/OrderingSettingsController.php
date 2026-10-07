<?php

namespace App\Http\Controllers\Api;

use App\Enums\Fulfilment;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateOrderingRequest;
use Illuminate\Http\JsonResponse;

/**
 * How guests send their orders, chosen on the Features page: on WhatsApp, or
 * in the menu with delivery and/or pickup. Ordering at the table is its own
 * feature (the `dine_in` switch) and is not set here. One way at a time; whether
 * ordering is on at all stays the `orders` switch (FeaturesController).
 */
class OrderingSettingsController extends Controller
{
    use ResolvesRestaurant;

    public function update(UpdateOrderingRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        // Stored in a fixed order, whatever order they were ticked in.
        $types = array_values(array_intersect(Fulfilment::away(), $request->validated('types')));

        $restaurant->update([
            'order_mode' => $request->validated('mode'),
            'order_types' => $types,
        ]);

        return response()->json(['data' => [
            'mode' => $restaurant->order_mode,
            'types' => $restaurant->orderTypes(),
        ]]);
    }
}
