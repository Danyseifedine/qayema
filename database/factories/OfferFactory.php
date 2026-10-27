<?php

namespace Database\Factories;

use App\Enums\OfferDiscount;
use App\Enums\OfferPlacement;
use App\Models\Offer;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Offer>
 */
class OfferFactory extends Factory
{
    protected $model = Offer::class;

    /**
     * A banner, always live, with no discount.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'title' => ['en' => fake()->randomElement(['Happy hour', 'Sunday brunch', 'Family deal'])],
            'description' => ['en' => fake()->sentence()],
            'placements' => [OfferPlacement::Banner->value],
        ];
    }

    /** @param  array<int, OfferPlacement>  $placements */
    public function in(OfferPlacement ...$placements): static
    {
        return $this->state(fn (): array => ['placements' => array_map(fn (OfferPlacement $placement): string => $placement->value, $placements)]);
    }

    public function percentOff(float $percent): static
    {
        return $this->state(fn (): array => ['discount_type' => OfferDiscount::Percent, 'discount_value' => $percent]);
    }

    public function amountOff(float $amount): static
    {
        return $this->state(fn (): array => ['discount_type' => OfferDiscount::Amount, 'discount_value' => $amount]);
    }

    public function paused(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}
