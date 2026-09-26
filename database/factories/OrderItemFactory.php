<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderItem>
 */
class OrderItemFactory extends Factory
{
    protected $model = OrderItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $price = fake()->randomFloat(2, 3, 30);
        $quantity = fake()->numberBetween(1, 3);

        return [
            'order_id' => Order::factory(),
            'dish_id' => null,
            'name' => fake()->words(2, true),
            'unit_price' => number_format($price, 2, '.', ''),
            'quantity' => $quantity,
            'line_total' => number_format($price * $quantity, 2, '.', ''),
        ];
    }
}
