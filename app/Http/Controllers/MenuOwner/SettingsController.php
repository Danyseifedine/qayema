<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\SettingsRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    private const DEFAULTS = [
        'show_cover_image' => true,
        'show_prices' => true,
        'show_phone_number' => true,
        'show_address' => true,
        'show_social_links' => true,
        'show_ingredients' => true,
        'enable_share' => true,
        'share_button_position' => 'bottom_right',
        'menu_direction' => 'ltr',
    ];

    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;
        $settings = array_merge(self::DEFAULTS, $restaurant?->template_settings ?? []);

        return view('dashboard.settings.index', compact('restaurant', 'settings'));
    }

    public function update(SettingsRequest $request): RedirectResponse
    {
        $restaurant = $request->user()->restaurant;
        $restaurant->update(['template_settings' => $request->validated()]);

        session()->flash('success', __('menu_owner.common.messages.settings_updated'));

        return redirect()->route('menu-owner.settings.index');
    }
}
