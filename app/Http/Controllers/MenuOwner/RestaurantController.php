<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\RestaurantRequest;
use App\Models\Restaurant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RestaurantController extends Controller
{
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

        if ($restaurant) {
            $restaurant->update($data);
            $message = __('menu_owner.common.messages.restaurant_updated');
        } else {
            $data['user_id'] = $user->id;
            $data['dish_limit'] = 40;
            $data['category_limit'] = 10;
            $data['social_link_limit'] = 10;
            $restaurant = Restaurant::create($data);
            $message = __('menu_owner.common.messages.restaurant_created');
        }

        $this->syncMedia($request, $restaurant, 'logo_key', 'delete_logo', 'logo', 'logo');
        $this->syncMedia($request, $restaurant, 'cover_image_key', 'delete_cover_image', 'cover_image', 'cover-image');

        return redirect()->route('menu-owner.restaurant.index')->with('success', $message);
    }

    private function syncMedia(
        RestaurantRequest $request,
        Restaurant $restaurant,
        string $keyField,
        string $deleteField,
        string $collection,
        string $mediaName,
    ): void {
        if ($request->filled($keyField)) {
            $path = storage_path('app/temp/'.$request->input($keyField).'.jpg');

            if (file_exists($path)) {
                $restaurant->clearMediaCollection($collection);
                $restaurant->addMedia($path)->usingName($mediaName)->toMediaCollection($collection);
            }

            return;
        }

        if ($request->boolean($deleteField)) {
            $restaurant->clearMediaCollection($collection);
        }
    }
}
