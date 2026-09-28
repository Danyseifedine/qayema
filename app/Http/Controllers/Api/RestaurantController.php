<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateRestaurantRequest;
use App\Http\Resources\RestaurantResource;
use App\Services\Media\MediaService;
use App\Services\Menu\MenuLanguages;
use App\Services\Menu\OpeningHours;
use Illuminate\Http\Request;

/**
 * The owner's restaurant profile: display name, description, contact details,
 * location and branding. The slug stays read-only. Always scoped to the
 * authenticated user's own restaurant, so there's no cross-restaurant surface.
 */
class RestaurantController extends Controller
{
    use ResolvesRestaurant;

    public function __construct(private readonly MediaService $media) {}

    public function show(Request $request): RestaurantResource
    {
        return new RestaurantResource($this->restaurant($request));
    }

    public function update(UpdateRestaurantRequest $request): RestaurantResource
    {
        $restaurant = $this->restaurant($request);

        // Text in the menu's current languages; any other language already in
        // the column is left in place, hidden, for if the owner switches back.
        $languages = $restaurant->menuLanguages();

        MenuLanguages::fill($restaurant, 'name', MenuLanguages::input($request, 'name', $languages), $languages);
        MenuLanguages::fill($restaurant, 'description', MenuLanguages::input($request, 'description', $languages), $languages);

        $restaurant->fill([
            'opening_hours' => OpeningHours::normalise((array) $request->validated('opening_hours')),
            'timezone' => $request->validated('timezone'),
            'google_maps_url' => $request->validated('google_maps_url'),
            'country_code' => $request->validated('country_code'),
            'phone' => $request->validated('phone'),
            'currency' => $request->validated('currency'),
        ])->save();

        // The logo can be replaced but never cleared (mandatory), so there is no delete flag.
        $this->media->sync($restaurant, $request->input('logo_key'), false, 'logo', 'logo');
        $this->media->sync($restaurant, $request->input('cover_image_key'), $request->boolean('delete_cover_image'), 'cover_image', 'cover');

        return new RestaurantResource($restaurant->fresh());
    }
}
