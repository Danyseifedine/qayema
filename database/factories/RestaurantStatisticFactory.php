<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\RestaurantStatistic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestaurantStatistic>
 */
class RestaurantStatisticFactory extends Factory
{
    protected $model = RestaurantStatistic::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'session_id' => fake()->uuid(),
            'device_type' => fake()->randomElement(['mobile', 'desktop', 'tablet']),
            'browser' => fake()->randomElement(['Chrome', 'Safari', 'Firefox']),
            'os' => fake()->randomElement(['iOS', 'Android', 'Windows']),
            'viewed_at' => now(),
            'time_spent' => fake()->numberBetween(0, 600),
            'page_views' => fake()->numberBetween(1, 10),
            'via_qr' => fake()->boolean(),
            'whatsapp_orders' => fake()->numberBetween(0, 3),
        ];
    }
}
