<?php

namespace Database\Factories;

use App\Enums\MenuEventType;
use App\Models\MenuEvent;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuEvent>
 */
class MenuEventFactory extends Factory
{
    protected $model = MenuEvent::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'session_id' => Str::random(40),
            'type' => MenuEventType::WhatsApp,
            'dish_id' => null,
            'category_id' => null,
            'value' => null,
            'occurred_at' => now(),
        ];
    }
}
