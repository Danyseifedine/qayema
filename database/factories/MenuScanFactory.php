<?php

namespace Database\Factories;

use App\Enums\MenuScanStatus;
use App\Models\MenuScan;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuScan>
 */
class MenuScanFactory extends Factory
{
    protected $model = MenuScan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'status' => MenuScanStatus::Pending,
            'result' => null,
            'error' => null,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>|null  $categories
     */
    public function completed(?array $categories = null): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => MenuScanStatus::Completed,
            'result' => [
                'categories' => $categories ?? [
                    [
                        'name' => 'Starters',
                        'dishes' => [
                            ['name' => 'Hummus', 'price' => 5.0, 'ingredients' => 'chickpeas, tahini'],
                            ['name' => 'Fattoush', 'price' => 6.5, 'ingredients' => null],
                        ],
                    ],
                ],
            ],
        ]);
    }
}
