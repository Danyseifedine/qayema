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
            'tier' => 'free',
            'capabilities' => null,
            'default_settings' => null,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    public function paid(): static
    {
        return $this->state(fn (array $attributes): array => [
            'tier' => 'paid',
        ]);
    }
}
