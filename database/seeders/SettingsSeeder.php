<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            [
                'title' => 'Menu Design',
                'key' => 'menu_design',
                'description' => 'Select the design style for your menu',
                'type' => 'string',
            ],
            [
                'title' => 'Currency Enabled',
                'key' => 'currency_enabled',
                'description' => 'Enable or disable currency display',
                'type' => 'boolean',
            ],
            [
                'title' => 'Exchange Currency',
                'key' => 'exchange_currency',
                'description' => 'The currency code for exchange rate (e.g., LBP, EUR)',
                'type' => 'string',
            ],
            [
                'title' => 'Exchange Rate',
                'key' => 'exchange_rate',
                'description' => 'Exchange rate from 1 USD to the exchange currency (e.g., 1 USD = X LBP)',
                'type' => 'float',
            ],
            [
                'title' => 'Show Prices',
                'key' => 'show_prices',
                'description' => 'Show or hide prices on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Language',
                'key' => 'language',
                'description' => 'Default language for the menu',
                'type' => 'string',
            ],
            [
                'title' => 'Show Dish Images',
                'key' => 'show_dish_image',
                'description' => 'Display dish images on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Category Images',
                'key' => 'show_category_image',
                'description' => 'Display category images on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Logo',
                'key' => 'show_logo',
                'description' => 'Display restaurant logo on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Cover Image',
                'key' => 'show_cover_image',
                'description' => 'Display cover image at the top of the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Restaurant Info',
                'key' => 'show_restaurant_info',
                'description' => 'Display restaurant name and description',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Address',
                'key' => 'show_address',
                'description' => 'Display restaurant address on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Phone Number',
                'key' => 'show_phone_number',
                'description' => 'Display restaurant phone number on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Social Links',
                'key' => 'show_social_links',
                'description' => 'Display social media links on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Show Ingredients',
                'key' => 'show_ingredients',
                'description' => 'Display dish ingredients on the menu',
                'type' => 'boolean',
            ],
            [
                'title' => 'Enable Share',
                'key' => 'enable_share',
                'description' => 'Enable menu sharing functionality',
                'type' => 'boolean',
            ],
            [
                'title' => 'Font Family',
                'key' => 'font_family',
                'description' => 'Font style for the menu (sans, serif, mono)',
                'type' => 'string',
            ],
            [
                'title' => 'Price Position (Default Design)',
                'key' => 'price_position',
                'description' => 'Where to display the price on dishes (only for default design)',
                'type' => 'string',
            ],
            [
                'title' => 'Collapsible Categories (Default Design)',
                'key' => 'category_collapsible',
                'description' => 'Enable collapsible category sections (only for default design)',
                'type' => 'boolean',
            ],
            [
                'title' => 'Category Default State (Default Design)',
                'key' => 'category_default_state',
                'description' => 'Default state for categories when collapsible is enabled (open or closed)',
                'type' => 'string',
            ],
        ];

        foreach ($settings as $settingData) {
            Setting::firstOrCreate(
                ['key' => $settingData['key']],
                [
                    'title' => $settingData['title'],
                    'key' => $settingData['key'],
                    'description' => $settingData['description'],
                    'type' => $settingData['type'],
                ]
            );
        }
    }
}
