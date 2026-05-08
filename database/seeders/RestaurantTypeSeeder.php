<?php

namespace Database\Seeders;

use App\Models\RestaurantType;
use Illuminate\Database\Seeder;

class RestaurantTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['name' => 'Restaurant', 'slug' => 'restaurant', 'icon' => '🍽️'],
            ['name' => 'Café',       'slug' => 'cafe',       'icon' => '☕'],
            ['name' => 'Bakery',     'slug' => 'bakery',     'icon' => '🥐'],
            ['name' => 'Food Truck', 'slug' => 'food-truck', 'icon' => '🚚'],
            ['name' => 'Bar',        'slug' => 'bar',        'icon' => '🍸'],
            ['name' => 'Cloud Kitchen', 'slug' => 'cloud-kitchen', 'icon' => '📦'],
            ['name' => 'Home Cook',  'slug' => 'home-cook',  'icon' => '🏠'],
        ];

        foreach ($types as $type) {
            RestaurantType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
