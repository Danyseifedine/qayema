<?php

namespace Tests\Integration\Services\Packages;

use App\Enums\Feature;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class EntitlementsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_limits_come_from_the_default_package(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(40, $restaurant->limitOf(Feature::DishLimit));
        $this->assertSame(8, $restaurant->limitOf(Feature::CategoryLimit));
        $this->assertSame(1, $restaurant->limitOf(Feature::SocialLinkLimit));
        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));
    }

    public function test_editing_the_default_package_changes_the_limit_for_every_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        // No manual flush: saving a package invalidates every restaurant on it.
        $this->defaultPackageSets(Feature::CategoryLimit, 25);

        $this->assertSame(25, $restaurant->limitOf(Feature::CategoryLimit));
        $this->assertSame(40, $restaurant->limitOf(Feature::DishLimit), 'Untouched features keep their seeded value.');
    }

    public function test_assigning_a_package_changes_the_limits(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $this->assertSame(40, $restaurant->limitOf(Feature::DishLimit));

        $restaurant->update(['package_id' => Package::findBySlug('pro')->id]);

        $this->assertSame(150, $restaurant->fresh()->limitOf(Feature::DishLimit));
        $this->assertTrue($restaurant->fresh()->entitlements()->can(Feature::Appearance));
    }

    public function test_an_expired_package_is_kept_never_dropped_to_the_default(): void
    {
        $restaurant = Restaurant::factory()->create([
            'template_id' => null,
            'package_id' => Package::findBySlug('premium')->id,
            'package_ends_at' => now()->addDay(),
        ]);

        $this->assertSame(1000, $restaurant->limitOf(Feature::DishLimit));

        $restaurant->update(['package_ends_at' => now()->subDay()]);
        $restaurant = $restaurant->fresh();

        $this->assertTrue($restaurant->packageExpired());
        $this->assertSame('premium', $restaurant->effectivePackage()->slug);
        $this->assertSame(1000, $restaurant->limitOf(Feature::DishLimit));
    }

    public function test_an_unlimited_limit_is_never_reached_and_a_grant_cannot_shrink_it(): void
    {
        $restaurant = Restaurant::factory()->create([
            'template_id' => null,
            'package_id' => Package::findBySlug('custom')->id,
        ]);

        $this->assertNull($restaurant->limitOf(Feature::DishLimit));
        $this->assertFalse($restaurant->hasReached(Feature::DishLimit));

        FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertNull($restaurant->fresh()->limitOf(Feature::DishLimit), 'Unlimited plus a grant is still unlimited.');
    }

    public function test_a_set_limit_overrides_the_package_lower_or_higher_or_unlimited(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 50)->create();

        $lower = FeatureGrant::factory()->for($restaurant)->setTo(Feature::DishLimit, 20)->create();
        $this->assertSame(20, $restaurant->fresh()->limitOf(Feature::DishLimit), 'Set wins over the package and the added 50.');

        $lower->update(['value' => 500]);
        $this->assertSame(500, $restaurant->fresh()->limitOf(Feature::DishLimit));

        $lower->update(['value' => null]);
        $this->assertNull($restaurant->fresh()->limitOf(Feature::DishLimit), 'Set to unlimited.');

        $lower->delete();
        $this->assertSame(90, $restaurant->fresh()->limitOf(Feature::DishLimit), 'Back to the package plus what was added.');
    }

    public function test_an_on_off_feature_can_be_turned_off_or_on_whatever_the_package(): void
    {
        $premium = Restaurant::factory()->create(['template_id' => null, 'package_id' => Package::findBySlug('premium')->id]);
        FeatureGrant::factory()->for($premium)->setTo(Feature::Ordering, 0)->create();

        $this->assertFalse($premium->fresh()->entitlements()->can(Feature::Ordering));
        $this->assertSame(['ordering'], $premium->fresh()->entitlements()->turnedOff());

        $free = Restaurant::factory()->create(['template_id' => null]);
        FeatureGrant::factory()->for($free)->setTo(Feature::QrStudio, 1)->create();

        $this->assertTrue($free->fresh()->entitlements()->can(Feature::QrStudio));
        $this->assertSame([], $free->fresh()->entitlements()->turnedOff());
    }

    public function test_the_newest_set_value_wins_and_an_ended_one_stops(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null, 'package_id' => Package::findBySlug('premium')->id]);
        FeatureGrant::factory()->for($restaurant)->setTo(Feature::Analytics, 0)->create();
        $on = FeatureGrant::factory()->for($restaurant)->setTo(Feature::Analytics, 1)->create();

        $this->assertTrue($restaurant->fresh()->entitlements()->can(Feature::Analytics));
        $this->assertSame([], $restaurant->fresh()->entitlements()->turnedOff());

        $on->delete();
        $paused = FeatureGrant::factory()->for($restaurant)->setTo(Feature::QrStudio, 0)->create(['ends_at' => now()->addDay()]);
        $this->assertSame(['analytics', 'qr_studio'], $restaurant->fresh()->entitlements()->turnedOff());

        $this->travelTo(now()->addDays(2));
        $this->assertTrue($restaurant->fresh()->entitlements()->can(Feature::QrStudio), 'The pause ended on its own.');
        $this->assertModelExists($paused);
    }

    public function test_a_grant_stacks_on_top_of_the_default(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertSame(90, $restaurant->limitOf(Feature::DishLimit), '40 default + 50 granted.');
    }

    public function test_several_grants_of_the_same_feature_add_up(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        FeatureGrant::factory()->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)->create();
        FeatureGrant::factory()->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)->create();

        $this->assertSame(140, $restaurant->limitOf(Feature::DishLimit), '40 default + 50 + 50.');
    }

    public function test_a_flag_grant_unlocks_the_feature(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));

        FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::QrStudio)
            ->create();

        $this->assertTrue($restaurant->entitlements()->can(Feature::QrStudio));
    }

    public function test_an_expired_grant_does_not_apply(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::QrStudio)
            ->expired()
            ->create();

        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));
        $this->assertSame(40, $restaurant->limitOf(Feature::DishLimit));
    }

    public function test_deleting_a_grant_takes_the_allowance_away_again(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $grant = FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertSame(90, $restaurant->limitOf(Feature::DishLimit));

        $grant->delete();

        $this->assertSame(40, $restaurant->fresh()->limitOf(Feature::DishLimit), 'The cache is flushed on delete.');
    }

    public function test_a_feature_missing_from_a_package_falls_back_to_its_own_default(): void
    {
        // A package written before a feature existed carries no key for it.
        // That must read as the feature's default, not as zero.
        $package = Package::factory()->create(['features' => ['dish_limit' => 500]]);

        $restaurant = Restaurant::factory()->create([
            'template_id' => null,
            'package_id' => $package->id,
        ]);

        $this->assertSame(500, $restaurant->limitOf(Feature::DishLimit));
        $this->assertSame(8, $restaurant->limitOf(Feature::CategoryLimit), 'Feature::defaultValue() fills the gap.');
        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));
    }

    public function test_templates_grant_nothing(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->assertSame(40, $restaurant->limitOf(Feature::DishLimit));
        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));
    }

    public function test_a_new_restaurant_starts_on_the_default_package(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(Package::default()->id, $restaurant->package_id);
        $this->assertNotNull($restaurant->package_started_at);
        $this->assertNull($restaurant->package_ends_at);
    }

    public function test_a_flag_stored_as_null_reads_as_on(): void
    {
        // Null means unlimited, which for a flag is on.
        $package = Package::factory()->create(['features' => ['qr_studio' => null]]);
        $restaurant = Restaurant::factory()->create(['template_id' => null, 'package_id' => $package->id]);

        $this->assertTrue($restaurant->entitlements()->can(Feature::QrStudio));
    }
}
