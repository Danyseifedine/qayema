<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // The free template every new restaurant can pick. It's the one layout
        // whose colors the owner may change; paid templates ship fixed designs.
        Template::updateOrCreate(['slug' => 'classic'], [
            'name' => ['en' => 'Classic', 'ar' => 'كلاسيك'],
            'description' => [
                'en' => 'A clean, light layout that suits any restaurant.',
                'ar' => 'تصميم بسيط وفاتح يناسب كل المطاعم.',
            ],
            'price' => 0,
            'settings_schema' => [
                ['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A'],
                ['key' => 'background_color', 'type' => 'color', 'default' => '#FFFFFF'],
                ['key' => 'text_color', 'type' => 'color', 'default' => '#15120A'],
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }
}
