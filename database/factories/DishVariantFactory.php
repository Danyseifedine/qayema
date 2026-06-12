<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\DishVariant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DishVariant>
 */
class DishVariantFactory extends Factory
{
    protected $model = DishVariant::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dish_id' => Dish::factory(),
            'name' => ['en' => fake()->randomElement(['Small', 'Medium', 'Large'])],
            'price' => fake()->randomFloat(2, 1, 50),
            'is_available' => true,
            'display_order' => 0,
        ];
    }
}
