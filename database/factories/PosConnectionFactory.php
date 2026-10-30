<?php

namespace Database\Factories;

use App\Models\PosConnection;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PosConnection>
 */
class PosConnectionFactory extends Factory
{
    protected $model = PosConnection::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'address' => 'https://pos.test',
            'key' => 'menu_'.Str::random(43),
            'signing_secret' => Str::random(48),
            'pos_uuid' => (string) Str::uuid(),
            'company' => 'Hamra Bites',
            'branch' => 'Hamra',
            'currency' => 'USD',
            'locales' => ['en', 'ar'],
        ];
    }
}
