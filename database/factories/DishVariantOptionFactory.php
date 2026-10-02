<?php

namespace Database\Factories;

use App\Models\DishVariant;
use App\Models\DishVariantOption;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DishVariantOption>
 */
class DishVariantOptionFactory extends Factory
{
    protected $model = DishVariantOption::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dish_variant_id' => DishVariant::factory(),
            'name' => ['en' => fake()->unique()->word()],
            'price' => 0,
            'display_order' => 0,
        ];
    }
}
