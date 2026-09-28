<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\PackageChange;
use App\Models\Restaurant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PackageChange>
 */
class PackageChangeFactory extends Factory
{
    protected $model = PackageChange::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_id' => Restaurant::factory(),
            'from_package_id' => fn (): ?int => Package::default()?->id,
            'to_package_id' => fn (): ?int => Package::findBySlug('pro')?->id,
            'starts_at' => now(),
            'ends_at' => null,
            'changed_by' => null,
            'note' => null,
        ];
    }
}
