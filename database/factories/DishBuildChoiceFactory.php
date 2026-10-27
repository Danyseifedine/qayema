<?php

namespace Database\Factories;

use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DishBuildChoice>
 */
class DishBuildChoiceFactory extends Factory
{
    protected $model = DishBuildChoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dish_build_id' => DishBuild::factory(),
            'dish_id' => null,
            'name' => ['en' => fake()->word()],
            'price' => '10.00',
            'part_prices' => null,
            'display_order' => 0,
        ];
    }
}
