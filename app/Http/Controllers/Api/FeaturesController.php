<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateFeaturesRequest;
use Illuminate\Http\JsonResponse;

/**
 * The dashboard's Features page: which optional features the owner has
 * switched off (see Restaurant::OPTIONAL_FEATURES for what each one does).
 * Nothing a feature holds is deleted; switching it back on brings it all back.
 */
class FeaturesController extends Controller
{
    use ResolvesRestaurant;

    public function update(UpdateFeaturesRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        $restaurant->update(['switched_off' => array_values($request->validated('off'))]);

        return response()->json(['data' => ['off' => $restaurant->switchedOff()]]);
    }
}
