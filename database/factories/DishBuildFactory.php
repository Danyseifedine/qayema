<?php

namespace Database\Factories;

use App\Enums\BuildShape;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DishBuild>
 */
class DishBuildFactory extends Factory
{
    protected $model = DishBuild::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'dish_id' => Dish::factory(),
            'name' => ['en' => 'Your pizza'],
            'shape' => BuildShape::Split,
            'parts' => [1, 2],
            'category_id' => null,
            'display_order' => 0,
        ];
    }

    /** Whole pieces: three scoops, each a whole item. */
    public function pieces(): static
    {
        return $this->state(['name' => ['en' => 'Your scoops'], 'shape' => BuildShape::Pieces, 'parts' => [3]]);
    }

    /**
     * Its own choices in order, as name => price, or name => [price, part prices].
     *
     * @param  array<string, float|int|string|array{0: float|int|string, 1: array<string, string>}>  $choices
     */
    public function withChoices(array $choices = ['Margherita' => 12, 'Pepperoni' => 16]): static
    {
        return $this->afterCreating(function (DishBuild $build) use ($choices): void {
            $order = 0;

            foreach ($choices as $name => $price) {
                [$whole, $parts] = is_array($price) ? $price : [$price, null];
                DishBuildChoice::factory()->create([
                    'dish_build_id' => $build->id,
                    'name' => ['en' => $name],
                    'price' => $whole,
                    'part_prices' => $parts,
                    'display_order' => ++$order,
                ]);
            }
        });
    }
}
