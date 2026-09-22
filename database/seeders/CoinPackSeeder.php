<?php

namespace Database\Seeders;

use App\Models\CoinPack;
use Illuminate\Database\Seeder;

/**
 * Starter coin packs. The Paddle price ids are filled in from the admin panel
 * once the products exist in Paddle — a pack without one is simply not sellable
 * yet, so seeding them empty is safe.
 */
class CoinPackSeeder extends Seeder
{
    public function run(): void
    {
        $packs = [
            ['slug' => 'small', 'name' => ['en' => 'Small', 'ar' => 'صغيرة'], 'coins' => 500, 'sort_order' => 0],
            ['slug' => 'medium', 'name' => ['en' => 'Medium', 'ar' => 'متوسطة'], 'coins' => 1200, 'sort_order' => 1],
            ['slug' => 'large', 'name' => ['en' => 'Large', 'ar' => 'كبيرة'], 'coins' => 3000, 'sort_order' => 2],
        ];

        foreach ($packs as $pack) {
            CoinPack::updateOrCreate(['slug' => $pack['slug']], $pack + ['is_active' => true]);
        }
    }
}
