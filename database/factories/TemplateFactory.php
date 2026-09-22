<?php

namespace Database\Factories;

use App\Models\Template;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Template>
 */
class TemplateFactory extends Factory
{
    protected $model = Template::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ['en' => fake()->unique()->words(2, true)],
            'slug' => fake()->unique()->slug(2),
            'price' => 0,
            'settings_schema' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /** A template that costs coins to unlock. */
    public function paid(int $price = 500): static
    {
        return $this->state(fn (array $attributes): array => [
            'price' => $price,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * @param  array<int, array<string, mixed>>  $schema
     */
    public function withSettings(array $schema): static
    {
        return $this->state(fn (array $attributes): array => [
            'settings_schema' => $schema,
        ]);
    }
}
