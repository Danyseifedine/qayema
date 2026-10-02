<?php

namespace Database\Factories;

use App\Models\Dish;
use App\Models\DishVariant;
use App\Models\DishVariantOption;
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
            'name' => ['en' => 'Size'],
            'display_order' => 0,
        ];
    }

    /**
     * The options in order, as name => extra price.
     *
     * @param  array<string, float|int|string>  $options
     */
    public function withOptions(array $options = ['Small' => 0, 'Medium' => 1.5, 'Large' => 3]): static
    {
        return $this->afterCreating(function (DishVariant $variant) use ($options): void {
            $order = 0;

            foreach ($options as $name => $price) {
                DishVariantOption::factory()->create([
                    'dish_variant_id' => $variant->id,
                    'name' => ['en' => $name],
                    'price' => $price,
                    'display_order' => ++$order,
                ]);
            }
        });
    }
}
