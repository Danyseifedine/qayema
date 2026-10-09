<?php

namespace App\Http\Controllers\Api;

use App\Enums\Fulfilment;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateDineInRequest;
use App\Http\Requests\UpdateOrderingRequest;
use Illuminate\Http\JsonResponse;

/**
 * How guests send their orders, chosen on the Features page: on WhatsApp, or
 * in the menu with delivery and/or pickup. One way at a time; whether
 * ordering is on at all stays the `orders` switch (FeaturesController).
 * Ordering at the table is its own feature (the `dine_in` switch) with its
 * own way in, set apart (dineIn()), so neither save can undo the other.
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

    /** How orders at the table come in: the Table orders page, or WhatsApp. */
    public function dineIn(UpdateDineInRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $restaurant->update(['dine_in_mode' => $request->validated('mode')]);

        return response()->json(['data' => ['dine_in' => $restaurant->dine_in_mode]]);
    }
}
