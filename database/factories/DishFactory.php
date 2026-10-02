<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Dish>
 */
class DishFactory extends Factory
{
    protected $model = Dish::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'category_id' => null,
            'name' => ['en' => fake()->unique()->words(2, true)],
            'price' => fake()->randomFloat(2, 1, 100),
            'ingredients' => ['en' => fake()->sentence()],
            'is_available' => true,
            'display_order' => 0,
        ];
    }

    public function unavailable(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_available' => false,
        ]);
    }

    /**
     * Variants in order, as name => [option name => extra price].
     *
     * @param  array<string, array<string, float|int|string>>  $variants
     */
    public function withVariants(array $variants = ['Size' => ['Small' => 0, 'Medium' => 1.5, 'Large' => 3]]): static
    {
        return $this->afterCreating(function (Dish $dish) use ($variants): void {
            $order = 0;

            foreach ($variants as $name => $options) {
                DishVariant::factory()->withOptions($options)->create([
                    'dish_id' => $dish->id,
                    'name' => ['en' => $name],
                    'display_order' => ++$order,
                ]);
            }
        });
    }

    /**
     * Add-ons in order, as name => price.
     *
     * @param  array<string, float|int|string>  $addons
     */
    public function withAddons(array $addons = ['Extra cheese' => 1, 'Fries inside' => 1.5]): static
    {
        return $this->afterCreating(function (Dish $dish) use ($addons): void {
            $order = 0;

            foreach ($addons as $name => $price) {
                DishAddon::factory()->create([
                    'dish_id' => $dish->id,
                    'name' => ['en' => $name],
                    'price' => $price,
                    'display_order' => ++$order,
                ]);
            }
        });
    }
}
