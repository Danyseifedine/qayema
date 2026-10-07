<?php

namespace Database\Factories;

use App\Models\PreviousSlug;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PreviousSlug>
 */
class PreviousSlugFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'slug' => fake()->unique()->slug(2),
        ];
    }
}
