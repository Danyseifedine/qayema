<?php

namespace Tests\Integration\Models;

use App\Models\Package;
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

    public function test_paying_is_a_menu_switched_on_with_a_package_in_force_and_a_price_above_nothing(): void
    {
        $this->freezeTime();
        $pro = Package::findBySlug('pro');
        $free = Package::findBySlug('free');

        $onPro = Restaurant::factory()->create(['package_id' => $pro->id]);
        $dealOnFree = Restaurant::factory()->create(['package_id' => $free->id, 'price_cents' => 300]);
        Restaurant::factory()->create(['package_id' => $free->id]);
        // Given Pro for nothing: its own price wins over the package's.
        Restaurant::factory()->create(['package_id' => $pro->id, 'price_cents' => 0]);
        // Ended, or not started yet: not paying now.
        Restaurant::factory()->create(['package_id' => $pro->id, 'package_ends_at' => now()->subDay()]);
        Restaurant::factory()->create(['package_id' => $pro->id, 'package_started_at' => now()->addDay()]);
        // Switched off: not paying, whatever its price.
        Restaurant::factory()->create(['package_id' => $pro->id, 'is_active' => false]);
        Restaurant::factory()->create(['package_id' => $free->id, 'price_cents' => 300, 'is_active' => false]);

        $this->assertEqualsCanonicalizing(
            [$onPro->id, $dealOnFree->id],
            Restaurant::query()->paying()->pluck('id')->all(),
        );
    }

    public function test_paying_still_works_with_the_packages_joined(): void
    {
        Restaurant::factory()->create(['package_id' => Package::findBySlug('pro')->id, 'price_cents' => 500]);
        // Packages have an "active" switch too: the restaurant's is the one read.
        Package::findBySlug('pro')->update(['is_active' => false]);

        $total = Restaurant::query()
            ->paying()
            ->join('packages', 'packages.id', '=', 'restaurants.package_id')
            ->sum('restaurants.price_cents');

        $this->assertSame(500, (int) $total);
    }
}
