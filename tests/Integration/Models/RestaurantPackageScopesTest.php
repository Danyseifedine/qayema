<?php

namespace Tests\Integration\Models;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The three package-window scopes split every restaurant into exactly one of
 * active, scheduled and expired.
 */
class RestaurantPackageScopesTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_restaurant_falls_into_exactly_one_window(): void
    {
        $this->freezeTime();

        $forever = Restaurant::factory()->create(['package_started_at' => null, 'package_ends_at' => null]);
        $running = Restaurant::factory()->create(['package_started_at' => now()->subMonth(), 'package_ends_at' => now()->addMonth()]);
        $startsNow = Restaurant::factory()->create(['package_started_at' => now(), 'package_ends_at' => null]);
        $scheduled = Restaurant::factory()->create(['package_started_at' => now()->addDay(), 'package_ends_at' => now()->addYear()]);
        $endsNow = Restaurant::factory()->create(['package_started_at' => now()->subYear(), 'package_ends_at' => now()]);
        $expired = Restaurant::factory()->create(['package_started_at' => now()->subYear(), 'package_ends_at' => now()->subDay()]);

        $this->assertEqualsCanonicalizing(
            [$forever->id, $running->id, $startsNow->id],
            Restaurant::query()->packageActive()->pluck('id')->all(),
        );
        $this->assertSame([$scheduled->id], Restaurant::query()->packageScheduled()->pluck('id')->all());
        $this->assertEqualsCanonicalizing(
            [$endsNow->id, $expired->id],
            Restaurant::query()->packageExpired()->pluck('id')->all(),
        );
    }

    public function test_a_scheduled_restaurant_becomes_active_once_its_start_passes(): void
    {
        $restaurant = Restaurant::factory()->create(['package_started_at' => now()->addHour()]);

        $this->assertTrue(Restaurant::query()->packageScheduled()->whereKey($restaurant->id)->exists());

        $this->travel(2)->hours();

        $this->assertFalse(Restaurant::query()->packageScheduled()->whereKey($restaurant->id)->exists());
        $this->assertTrue(Restaurant::query()->packageActive()->whereKey($restaurant->id)->exists());
    }
}
