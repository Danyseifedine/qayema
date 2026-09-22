<?php

namespace Database\Factories;

use App\Models\CoinPack;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoinPack>
 */
class CoinPackFactory extends Factory
{
    protected $model = CoinPack::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $coins = fake()->randomElement([100, 500, 1000]);

        return [
            'slug' => fake()->unique()->slug(2),
            'name' => ['en' => "{$coins} coins"],
            'coins' => $coins,
            'paddle_price_id_sandbox' => 'pri_'.fake()->unique()->lexify('??????????'),
            'paddle_price_id_production' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function withCoins(int $coins): static
    {
        return $this->state(fn (array $attributes): array => [
            'coins' => $coins,
            'name' => ['en' => "{$coins} coins"],
        ]);
    }

    /** A pack with no Paddle price for this environment — not sellable. */
    public function unpriced(): static
    {
        return $this->state(fn (array $attributes): array => [
            'paddle_price_id_sandbox' => null,
            'paddle_price_id_production' => null,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
