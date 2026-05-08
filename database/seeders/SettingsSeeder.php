<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Settings table was removed in the restaurants/templates restructure.
        // Menu settings are now stored as template_settings JSON on the restaurants table.
    }
}
