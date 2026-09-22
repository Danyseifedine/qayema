<?php

namespace Database\Factories;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Models\RestaurantFeature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RestaurantFeature>
 */
class RestaurantFeatureFactory extends Factory
{
    protected $model = RestaurantFeature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'feature' => Feature::DishLimit,
            'value' => 10,
            'source' => 'admin',
            'reference' => null,
            'ends_at' => null,
        ];
    }

    public function forFeature(Feature $feature, int $value = 1): static
    {
        return $this->state(fn (array $attributes): array => [
            'feature' => $feature,
            'value' => $value,
        ]);
    }

    /** A grant that has already lapsed, so it must not count. */
    public function expired(): static
    {
        return $this->state(fn (array $attributes): array => [
            'ends_at' => now()->subDay(),
        ]);
    }
}
