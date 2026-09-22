<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // The design every new restaurant starts from. Its colors are owner
        // editable; a template with an empty schema ships a fixed design.
        Template::updateOrCreate(['slug' => 'classic'], [
            'name' => ['en' => 'Classic', 'ar' => 'كلاسيك'],
            'description' => [
                'en' => 'A clean, light layout that suits any restaurant.',
                'ar' => 'تصميم بسيط وفاتح يناسب كل المطاعم.',
            ],
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
