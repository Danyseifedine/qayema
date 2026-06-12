<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestaurantSocialLink>
 */
class RestaurantSocialLinkFactory extends Factory
{
    protected $model = RestaurantSocialLink::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'platform' => fake()->randomElement(['instagram', 'x', 'facebook', 'tiktok']),
            'url' => fake()->url(),
        ];
    }
}
