<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\DishAddon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DishAddon>
 */
class DishAddonFactory extends Factory
{
    protected $model = DishAddon::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dish_id' => Dish::factory(),
            'name' => ['en' => fake()->unique()->words(2, true)],
            'price' => fake()->randomFloat(2, 0.5, 3),
            'display_order' => 0,
        ];
    }
}
