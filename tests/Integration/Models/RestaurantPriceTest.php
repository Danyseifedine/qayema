<?php

namespace Tests\Integration\Models;

use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Menu\MenuCache;
use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class RestaurantPriceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function onPremium(array $attributes = []): Restaurant
    {
        return Restaurant::factory()->create(['package_id' => Package::findBySlug('premium')->id, ...$attributes]);
    }

    public function test_it_pays_its_own_price_when_one_is_set_else_the_packages(): void
    {
        $restaurant = $this->onPremium();
        $this->assertSame(Package::findBySlug('premium')->price_cents, $restaurant->monthlyPriceCents());

        $restaurant->update(['price_cents' => 600]);
        $this->assertSame(600, $restaurant->fresh()->monthlyPriceCents());
    }

    public function test_a_new_package_starts_at_its_own_price(): void
    {
        $restaurant = $this->onPremium(['price_cents' => 600]);

        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('pro'), null, null);

        $this->assertNull($restaurant->fresh()->price_cents);
        $this->assertSame(Package::findBySlug('pro')->price_cents, $restaurant->fresh()->monthlyPriceCents());
    }

    public function test_assigning_the_same_package_again_keeps_the_price(): void
    {
        $restaurant = $this->onPremium(['price_cents' => 600]);

        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('premium'), null, now()->addMonth());

        $this->assertSame(600, $restaurant->fresh()->price_cents);
    }

    public function test_a_price_set_with_the_new_package_is_kept(): void
    {
        $restaurant = $this->onPremium(['price_cents' => 600]);

        $restaurant->update(['package_id' => Package::findBySlug('pro')->id, 'price_cents' => 400]);

        $this->assertSame(400, $restaurant->fresh()->price_cents);
    }

    public function test_every_price_change_is_kept_in_the_history(): void
    {
        $restaurant = $this->onPremium();
        $restaurant->packageChangeNote = 'Friend discount';

        $restaurant->update(['price_cents' => 600]);

        $last = $restaurant->packageChanges()->latest('id')->first();
        $this->assertSame(600, $last->price_cents);
        $this->assertSame('Friend discount', $last->note);
        $this->assertSame(2, $restaurant->packageChanges()->count(), 'Created, then the price.');
    }

    public function test_the_price_and_whish_leave_the_cached_menu_alone(): void
    {
        $restaurant = $this->onPremium();
        $this->mock(MenuCache::class)->shouldNotReceive('forget');

        $restaurant->update(['price_cents' => 600, 'pays_with_whish' => true]);

        $this->assertTrue($restaurant->fresh()->pays_with_whish);
    }
}
