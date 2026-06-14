<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestaurantRequest;
use App\Models\Restaurant;
use App\Services\Global\MediaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function __construct(private readonly MediaService $media) {}

    public function index(Request $request): View
    {
        return view('dashboard.restaurant.index', [
            'restaurant' => $request->user()->restaurant,
        ]);
    }

    public function storeOrUpdate(RestaurantRequest $request): RedirectResponse
    {
        $user = $request->user();
        $restaurant = $user->restaurant;
        $data = $request->safe()->except(['logo_key', 'cover_image_key', 'delete_logo', 'delete_cover_image']);

        $data['is_active'] = $request->has('is_active');

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $slug = $data['slug'];
        $counter = 1;
        while (Restaurant::where('slug', $slug)->where('id', '!=', $restaurant?->id)->exists()) {
            $slug = $data['slug'].'-'.$counter;
            $counter++;
        }
        $data['slug'] = $slug;

        // Translatable content is written under the restaurant's base language,
        // preserving any translation already stored for the other locale.
        $locale = $data['default_locale'] ?? $restaurant?->default_locale ?? 'ar';
        foreach (['name', 'description', 'address'] as $translatable) {
            if (array_key_exists($translatable, $data) && ! is_array($data[$translatable])) {
                $existing = $restaurant?->getTranslations($translatable) ?? [];
                $data[$translatable] = array_merge($existing, [$locale => $data[$translatable]]);
            }
        }

        if ($restaurant) {
            $restaurant->update($data);
            $message = __('menu_owner.common.messages.restaurant_updated');
        } else {
            $data['user_id'] = $user->id;
            $restaurant = Restaurant::create($data);
            $message = __('menu_owner.common.messages.restaurant_created');
        }

        $this->media->sync($restaurant, $request->input('logo_key'), $request->boolean('delete_logo'), 'logo', 'logo');
        $this->media->sync($restaurant, $request->input('cover_image_key'), $request->boolean('delete_cover_image'), 'cover_image', 'cover-image');

        return redirect()->route('menu-owner.restaurant.index')->with('success', $message);
    }
}
