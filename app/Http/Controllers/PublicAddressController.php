<?php

namespace App\Http\Controllers;

use App\Enums\OrderChannel;
use App\Http\Requests\LookUpAddressRequest;
use App\Models\Restaurant;
use App\Services\Orders\ReverseGeocoder;
use Illuminate\Http\JsonResponse;

/**
 * "Use my location" in the cart: the guest's position as an address line,
 * which the cart puts in the address box for them to finish (building,
 * floor). Only for a menu that takes orders in the menu.
 */
class PublicAddressController extends Controller
{
    public function show(LookUpAddressRequest $request, Restaurant $restaurant, ReverseGeocoder $geocoder): JsonResponse
    {
        abort_unless($restaurant->is_active && $restaurant->orderChannel() === OrderChannel::Menu, 404);

        $locale = in_array($request->validated('locale'), $restaurant->menuLanguages(), true)
            ? (string) $request->validated('locale')
            : 'en';

        return response()->json(['data' => [
            'address' => $geocoder->address((float) $request->validated('lat'), (float) $request->validated('lng'), $locale),
        ]]);
    }
}
