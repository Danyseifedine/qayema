<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMenuSettingsRequest;
use App\Models\MenuSetting;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MenuSettingController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $menu = $user->currentMenu();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before configuring settings.');
        }

        // Get all available settings
        $allSettings = Setting::orderBy('title')->get();

        // Get existing menu settings
        $menuSettings = $menu->settings()->with('setting')->get()->keyBy('setting_id');

        // Group settings by category
        $groupedSettings = [
            'display' => [
                'title' => __('menu_owner.settings.groups.display'),
                'icon' => 'eye',
                'settings' => [],
            ],
            'currency' => [
                'title' => __('menu_owner.settings.groups.currency'),
                'icon' => 'currency-dollar',
                'settings' => [],
            ],
            'design' => [
                'title' => __('menu_owner.settings.groups.design'),
                'icon' => 'paint-brush',
                'settings' => [],
            ],
            'general' => [
                'title' => __('menu_owner.settings.groups.general'),
                'icon' => 'cog',
                'settings' => [],
            ],
        ];

        // Categorize settings
        foreach ($allSettings as $setting) {
            $menuSetting = $menuSettings->get($setting->id);
            $value = $menuSetting ? $menuSetting->value : null;

            if ($value === null) {
                $value = config("menu_settings.defaults.{$setting->key}");
            }

            $settingData = [
                'id' => $setting->id,
                'key' => $setting->key,
                'title' => $setting->title,
                'description' => $setting->description,
                'type' => $setting->type,
                'value' => $value,
                'menu_setting_id' => $menuSetting?->id,
            ];

            // Categorize
            if (in_array($setting->key, ['show_dish_image', 'show_category_image', 'show_logo', 'show_cover_image', 'show_restaurant_info', 'show_address', 'show_phone_number', 'show_social_links', 'show_ingredients', 'show_loading_page', 'menu_direction'])) {
                $groupedSettings['display']['settings'][] = $settingData;
            } elseif (in_array($setting->key, ['currency_enabled', 'exchange_currency', 'exchange_rate', 'show_prices'])) {
                $groupedSettings['currency']['settings'][] = $settingData;
            } elseif (in_array($setting->key, ['menu_design', 'font_family', 'price_position', 'category_layout', 'dish_layout', 'category_collapsible', 'category_default_state'])) {
                $groupedSettings['design']['settings'][] = $settingData;
            } else {
                $groupedSettings['general']['settings'][] = $settingData;
            }
        }

        // Sort design settings: menu_design first, then default-only settings, then font_family
        if (isset($groupedSettings['design']['settings'])) {
            $designOrder = ['menu_design', 'category_layout', 'dish_layout', 'price_position', 'category_collapsible', 'category_default_state', 'font_family'];
            usort($groupedSettings['design']['settings'], function ($a, $b) use ($designOrder) {
                $posA = array_search($a['key'], $designOrder);
                $posB = array_search($b['key'], $designOrder);
                $posA = $posA === false ? 999 : $posA;
                $posB = $posB === false ? 999 : $posB;

                return $posA - $posB;
            });
        }

        return view('menu-owner.settings.index', [
            'menu' => $menu,
            'groupedSettings' => $groupedSettings,
        ]);
    }

    public function update(UpdateMenuSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->currentMenu();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before configuring settings.');
        }

        $settings = $request->input('settings', []);

        // Get all available settings to ensure we process all of them
        $allSettings = Setting::all();

        foreach ($allSettings as $setting) {
            $value = $settings[$setting->id] ?? null;

            // Handle boolean values
            if ($setting->type === 'boolean') {
                $value = ($value === '1' || $value === 1 || $value === true) ? true : false;
            }

            // Handle null/empty values for optional fields
            if ($value === null || $value === '' || $value === '0') {
                if (in_array($setting->key, ['exchange_currency', 'exchange_rate'])) {
                    $value = null;
                } elseif ($setting->type === 'boolean') {
                    $value = false;
                } else {
                    // Skip updating if value is empty and it's not a boolean or optional field
                    continue;
                }
            }

            MenuSetting::updateOrCreate(
                [
                    'menu_id' => $menu->id,
                    'setting_id' => $setting->id,
                ],
                [
                    'value' => $value,
                ]
            );
        }

        return redirect()->route('menu-owner.settings.index')
            ->with('success', __('menu_owner.settings.settings_updated'));
    }
}
