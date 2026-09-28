<?php

namespace Tests\Integration\Models;

use App\Enums\Feature;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A feature a new package or grant brings arrives switched on; one the owner
 * already had keeps whatever they chose on the Features page.
 */
class SwitchedOffFeaturesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_moving_up_to_premium_switches_on_everything_it_brings(): void
    {
        $restaurant = $this->owner(['switched_off' => ['orders', 'qr', 'analytics', 'languages']]);

        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('premium'));

        $this->assertSame([], $restaurant->fresh()->switchedOff());
        $this->assertTrue($restaurant->fresh()->takesOrders());
    }

    public function test_a_feature_the_old_package_had_keeps_the_owners_choice(): void
    {
        // Pro has analytics and a second language; Premium adds orders and the QR studio.
        $restaurant = $this->ownerOn('pro', ['switched_off' => ['analytics', 'orders']]);

        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('premium'));

        $this->assertSame(['analytics'], $restaurant->fresh()->switchedOff());
    }

    public function test_moving_down_forgets_no_choice(): void
    {
        $restaurant = $this->ownerOn('premium', ['switched_off' => ['orders']]);

        app(PackageAssigner::class)->assign($restaurant, Package::default());

        $this->assertSame(['orders'], $restaurant->fresh()->switchedOff());
    }

    public function test_a_restaurant_with_its_package_loaded_still_sees_the_new_one(): void
    {
        $restaurant = $this->owner(['switched_off' => ['orders']]);
        $restaurant->load('package');

        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('premium'));

        $this->assertSame([], $restaurant->switchedOff());
    }

    public function test_a_granted_flag_arrives_switched_on(): void
    {
        $restaurant = $this->owner(['switched_off' => ['orders', 'qr']]);

        FeatureGrant::factory()->create([
            'restaurant_id' => $restaurant->id,
            'feature' => Feature::Ordering,
            'value' => 1,
        ]);

        $this->assertSame(['qr'], $restaurant->fresh()->switchedOff());
    }

    public function test_a_grant_for_something_already_in_reach_changes_nothing(): void
    {
        $restaurant = $this->ownerOn('premium', ['switched_off' => ['orders']]);

        FeatureGrant::factory()->create([
            'restaurant_id' => $restaurant->id,
            'feature' => Feature::Ordering,
            'value' => 1,
        ]);

        $this->assertSame(['orders'], $restaurant->fresh()->switchedOff());
    }
}
