<?php

namespace Database\Factories;

use App\Models\Feature;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Feature>
 */
class FeatureFactory extends Factory
{
    protected $model = Feature::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => ['en' => fake()->words(2, true)],
            'kind' => 'boolean',
            'is_addon' => false,
            'is_active' => true,
        ];
    }

    public function limit(): static
    {
        return $this->state(fn (array $attributes): array => [
            'kind' => 'limit',
        ]);
    }

    public function addon(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_addon' => true,
        ]);
    }
}
