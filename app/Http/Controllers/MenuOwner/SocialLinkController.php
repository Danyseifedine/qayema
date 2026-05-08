<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\SocialLinkRequest;
use App\Models\RestaurantSocialLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SocialLinkController extends Controller
{
    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;

        $socialLinks = $restaurant
            ? RestaurantSocialLink::where('restaurant_id', $restaurant->id)
                ->orderBy('created_at')
                ->get()
            : collect();

        return view('dashboard.social-links.index', [
            'socialLinks' => $socialLinks,
            'restaurant' => $restaurant,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return redirect()->route('menu-owner.restaurant.index')
                ->with('error', __('menu_owner.common.messages.setup_first'));
        }

        return view('dashboard.social-links.form', [
            'socialLink' => null,
            'restaurant' => $restaurant,
        ]);
    }

    public function store(SocialLinkRequest $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return redirect()->route('menu-owner.restaurant.index')
                ->with('error', __('menu_owner.common.messages.setup_first'));
        }

        if ($restaurant->hasReachedSocialLinkLimit()) {
            return redirect()->route('menu-owner.social-links.index')
                ->with('error', __('menu_owner.common.messages.limit_social'));
        }

        $data = $request->validated();
        $data['restaurant_id'] = $restaurant->id;

        RestaurantSocialLink::create($data);

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', __('menu_owner.common.messages.social_link_created'));
    }

    public function edit(Request $request, RestaurantSocialLink $socialLink): View
    {
        $this->authorize('update', $socialLink);

        return view('dashboard.social-links.form', [
            'socialLink' => $socialLink,
            'restaurant' => $socialLink->restaurant,
        ]);
    }

    public function update(SocialLinkRequest $request, RestaurantSocialLink $socialLink): RedirectResponse
    {
        $this->authorize('update', $socialLink);

        $socialLink->update($request->validated());

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', __('menu_owner.common.messages.social_link_updated'));
    }

    public function destroy(RestaurantSocialLink $socialLink): RedirectResponse
    {
        $this->authorize('delete', $socialLink);

        $socialLink->delete();

        return redirect()->route('menu-owner.social-links.index')
            ->with('success', __('menu_owner.common.messages.social_link_deleted'));
    }
}
