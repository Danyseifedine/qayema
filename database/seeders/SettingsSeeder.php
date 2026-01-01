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
                'title' => 'Theme Color',
                'key' => 'theme_color',
                'description' => 'Primary color for the menu theme',
                'type' => 'string',
            ],
            [
                'title' => 'Currency',
                'key' => 'currency',
                'description' => 'Currency code for menu prices',
                'type' => 'string',
            ],
            [
                'title' => 'Language',
                'key' => 'language',
                'description' => 'Default language for the menu',
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
