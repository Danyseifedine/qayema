<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Restaurant>
 */
class RestaurantFactory extends Factory
{
    protected $model = Restaurant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->company(),
            'slug' => fake()->unique()->slug(3),
            'description' => fake()->sentence(),
            'country_code' => 'LB',
            'phone' => fake()->numerify('70######'),
            'is_active' => true,
            'dish_limit' => 40,
            'category_limit' => 10,
            'social_link_limit' => 10,
            'currency' => 'USD',
            'preferred_language' => 'en',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
