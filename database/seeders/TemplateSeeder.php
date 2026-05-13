<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Default',
                'slug' => 'default',
                'description' => 'A clean, light, classic layout suitable for any restaurant.',
                'is_active' => true,
            ],
            [
                'name' => 'Dark',
                'slug' => 'dark',
                'description' => 'A bold, dark theme that makes your menu stand out.',
                'is_active' => true,
            ],
            [
                'name' => 'Elegant',
                'slug' => 'elegant',
                'description' => 'A refined, elegant design for fine-dining establishments.',
                'is_active' => true,
            ],
            [
                'name' => 'Minimal',
                'slug' => 'minimal',
                'description' => 'A stripped-back, minimal layout that lets the food speak.',
                'is_active' => true,
            ],
            [
                'name' => 'Bold',
                'slug' => 'bold',
                'description' => 'A high-contrast, expressive design for street food and casual eateries.',
                'is_active' => true,
            ],
        ];

        foreach ($templates as $data) {
            Template::firstOrCreate(['slug' => $data['slug']], $data);
        }
    }
}
