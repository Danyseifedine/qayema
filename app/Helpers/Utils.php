<?php

use App\Models\Setting;

class Utils {}

if (! function_exists('setting')) {
    /**
     * Get or set a setting value
     *
     * @param  string|null  $key  The setting key
     * @param  mixed  $default  Default value if setting doesn't exist
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return Setting::getAllGrouped();
        }

        return Setting::get($key, $default);
    }
}

if (! function_exists('settings')) {
    /**
     * Get settings by group or all settings
     *
     * @param  string|null  $group  The settings group
     */
    function settings(?string $group = null): array
    {
        if ($group === null) {
            return Setting::getAllGrouped();
        }

        return Setting::getByGroup($group);
    }
}
