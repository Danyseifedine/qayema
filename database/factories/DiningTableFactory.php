<?php

namespace Database\Factories;

use App\Models\DiningTable;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DiningTable>
 */
class DiningTableFactory extends Factory
{
    protected $model = DiningTable::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'name' => 'Table '.fake()->unique()->numberBetween(1, 999),
            'sort_order' => 0,
        ];
    }
}
