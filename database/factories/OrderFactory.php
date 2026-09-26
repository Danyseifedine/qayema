<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    protected $model = Order::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'reference' => Order::newReference(),
            'status' => OrderStatus::Placed,
            'currency' => 'USD',
            'total' => '0.00',
            'note' => null,
            'placed_at' => now(),
        ];
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
