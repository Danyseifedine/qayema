<?php

namespace Database\Factories;

use App\Models\MenuSession;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuSession>
 */
class MenuSessionFactory extends Factory
{
    protected $model = MenuSession::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'session_id' => Str::random(40),
            'device_type' => 'mobile',
            'browser' => 'Chrome',
            'os' => 'Android',
            'locale' => 'en',
            'viewed_at' => now(),
            'via_qr' => false,
        ];
    }

    /** A visit opened from a QR code (`?qr=1`). */
    public function viaQr(): static
    {
        return $this->state(fn (array $attributes): array => ['via_qr' => true]);
    }
}
