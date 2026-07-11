<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        // Only the default template ships for now. The owner must actively choose
        // it (or any future template) from the dashboard before they can use it.
        Template::firstOrCreate(['slug' => 'default'], [
            'name' => 'Default',
            'slug' => 'default',
            'description' => 'A clean, light, classic layout suitable for any restaurant.',
            'is_active' => true,
        ]);
    }
}
