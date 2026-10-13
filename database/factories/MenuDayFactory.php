<?php

namespace Database\Factories;

use App\Models\MenuDay;
use App\Models\Restaurant;
use App\Support\HyperLogLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MenuDay>
 */
class MenuDayFactory extends Factory
{
    protected $model = MenuDay::class;

    /**
     * An old day with nothing on it; states fill in what a test needs.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'day' => now()->subYear()->toDateString(),
            'views' => 0,
            'qr_scans' => 0,
            'visitors' => (new HyperLogLog)->toStored(),
            'carted' => (new HyperLogLog)->toStored(),
            'hours' => array_fill(0, 24, 0),
            'languages' => [],
            'devices' => [],
            'browsers' => [],
            'systems' => [],
            'actions' => [],
            'dishes_added' => [],
            'categories_opened' => [],
            'searches' => [],
            'missed_searches' => [],
        ];
    }

    /**
     * Visits from these visitor ids, each its own view, at noon.
     *
     * @param  array<int, string>  $visitors
     */
    public function visitedBy(array $visitors, int $qrScans = 0): static
    {
        return $this->state(function (array $attributes) use ($visitors, $qrScans): array {
            $hours = array_fill(0, 24, 0);
            $hours[12] = count($visitors);

            return [
                'views' => count($visitors),
                'qr_scans' => $qrScans,
                'visitors' => HyperLogLog::of($visitors)->toStored(),
                'hours' => $hours,
                'languages' => ['en' => count($visitors)],
            ];
        });
    }
}
