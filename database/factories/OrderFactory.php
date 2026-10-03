<?php

namespace Database\Factories;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
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
            'channel' => OrderChannel::WhatsApp,
            'currency' => 'USD',
            'total' => '0.00',
            'note' => null,
            'placed_at' => now(),
        ];
    }

    /** Placed in the menu, by a guest who left a phone and an address. */
    public function inMenu(Fulfilment $fulfilment = Fulfilment::Delivery): static
    {
        return $this->state(fn (array $attributes): array => [
            'channel' => OrderChannel::Menu,
            'fulfilment' => $fulfilment,
            'guest_name' => 'Rami',
            'guest_phone' => '+96170123456',
            'address' => $fulfilment === Fulfilment::Delivery ? 'Hamra Street, near the bank' : null,
        ]);
    }

    public function status(OrderStatus $status): static
    {
        return $this->state(fn (array $attributes): array => ['status' => $status]);
    }
}
