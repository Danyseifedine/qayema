<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\DishAddonGroup;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DishAddonGroup>
 */
class DishAddonGroupFactory extends Factory
{
    protected $model = DishAddonGroup::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dish_id' => Dish::factory(),
            'name' => ['en' => fake()->randomElement(['Sauces', 'Extras', 'Sides'])],
            'min_select' => 0,
            'max_select' => null,
            'display_order' => 0,
        ];
    }
}
