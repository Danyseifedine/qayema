<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRestaurantSlugRequest;
use App\Http\Resources\RestaurantResource;

/**
 * The menu's link, /{slug}. Changing it keeps the old one working: the
 * restaurant's save hook records it (PreviousSlug) and a visit to it
 * forwards here (App\Support\FormerMenuLink), so printed QR codes do not
 * break.
 */
class RestaurantSlugController extends Controller
{
    use ResolvesRestaurant;

    public function update(UpdateRestaurantSlugRequest $request): RestaurantResource
    {
        $restaurant = $this->restaurant($request);
        $restaurant->update(['slug' => $request->validated('slug')]);

        return new RestaurantResource($restaurant->refresh());
    }
}
