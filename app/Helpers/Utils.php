<?php

use App\Models\Setting;

class Utils {}

if (! function_exists('menu_setting')) {
    /**
     * Get a menu setting value by key
     *
     * @param  int  $menuId  The menu ID
     * @param  string  $key  The setting key
     * @param  mixed  $default  Default value if setting doesn't exist
     */
    function menu_setting(int $menuId, string $key, mixed $default = null): mixed
    {
        $setting = \App\Models\Setting::where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        $menuSetting = \App\Models\MenuSetting::where('menu_id', $menuId)
            ->where('setting_id', $setting->id)
            ->first();

        return $menuSetting?->value ?? $default;
    }
}
