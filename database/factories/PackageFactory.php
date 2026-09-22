<?php

namespace Database\Factories;

use App\Enums\Feature;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Package>
 */
class PackageFactory extends Factory
{
    protected $model = Package::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(1),
            'name' => ['en' => fake()->word()],
            'description' => ['en' => fake()->sentence()],
            'price_cents' => fake()->numberBetween(0, 5000),
            'currency' => 'USD',
            'is_contact_only' => false,
            'is_default' => false,
            'sort_order' => 0,
            'features' => collect(Feature::cases())
                ->mapWithKeys(fn (Feature $feature): array => [$feature->value => $feature->defaultValue()])
                ->all(),
        ];
    }

    /**
     * @param  array<string, int|null>  $features
     */
    public function withFeatures(array $features): static
    {
        return $this->state(fn (array $attributes): array => [
            'features' => array_merge($attributes['features'] ?? [], $features),
        ]);
    }

    /** A package whose price is not published — the owner has to ask. */
    public function contactOnly(): static
    {
        return $this->state(fn (array $attributes): array => [
            'price_cents' => null,
            'is_contact_only' => true,
        ]);
    }
}
