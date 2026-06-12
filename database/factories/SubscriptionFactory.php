<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'subscribable_type' => Template::class,
            'subscribable_id' => Template::factory(),
            'period' => 'monthly',
            'price' => 9.99,
            'currency' => 'USD',
            'status' => 'active',
            'starts_at' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
            'provider' => 'manual',
        ];
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'expired',
            'current_period_end' => now()->subMonth(),
        ]);
    }

    public function pastDue(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => 'past_due',
            'current_period_end' => now()->subDays(2),
        ]);
    }
}
