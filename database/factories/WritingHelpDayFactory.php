<?php

namespace Database\Factories;

use App\Models\Restaurant;
use App\Models\WritingHelpDay;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WritingHelpDay>
 */
class WritingHelpDayFactory extends Factory
{
    protected $model = WritingHelpDay::class;

    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'day' => now()->toDateString(),
            'uses' => 3,
            'input_tokens' => 900,
            'output_tokens' => 120,
        ];
    }
}
