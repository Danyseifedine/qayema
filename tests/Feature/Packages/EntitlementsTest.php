<?php

namespace Tests\Feature\Packages;

use App\Enums\Feature;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntitlementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_limits_come_from_the_default_package(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertSame(8, $restaurant->category_limit);
        $this->assertSame(1, $restaurant->social_link_limit);
        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));
    }

    public function test_editing_the_default_package_changes_the_limit_for_every_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        // No manual flush: saving a package invalidates every restaurant on it.
        Package::default()->setFeature(Feature::CategoryLimit, 25);

        $this->assertSame(25, $restaurant->category_limit);
        $this->assertSame(40, $restaurant->dish_limit, 'Untouched features keep their seeded value.');
    }

    public function test_assigning_a_package_changes_the_limits(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $this->assertSame(40, $restaurant->dish_limit);

        $restaurant->update(['package_id' => Package::findBySlug('pro')->id]);

        $this->assertSame(150, $restaurant->fresh()->dish_limit);
        $this->assertTrue($restaurant->fresh()->entitlements()->can(Feature::Appearance));
    }

    public function test_an_expired_package_falls_back_to_the_default_one(): void
    {
        $restaurant = Restaurant::factory()->create([
            'template_id' => null,
            'package_id' => Package::findBySlug('premium')->id,
            'package_ends_at' => now()->addDay(),
        ]);

        $this->assertSame(500, $restaurant->dish_limit);

        $restaurant->update(['package_ends_at' => now()->subDay()]);
        $restaurant = $restaurant->fresh();

        $this->assertTrue($restaurant->packageExpired());
        $this->assertSame('free', $restaurant->effectivePackage()->slug);
        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertSame(
            'premium',
            $restaurant->package->slug,
            'The assignment is kept so an admin can still see what expired.',
        );
    }

    public function test_an_unlimited_limit_is_never_reached_and_a_grant_cannot_shrink_it(): void
    {
        $restaurant = Restaurant::factory()->create([
            'template_id' => null,
            'package_id' => Package::findBySlug('custom')->id,
        ]);

        $this->assertNull($restaurant->dish_limit);
        $this->assertTrue($restaurant->entitlements()->isUnlimited(Feature::DishLimit));
        $this->assertFalse($restaurant->hasReachedDishLimit());

        FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertNull($restaurant->fresh()->dish_limit, 'Unlimited plus a grant is still unlimited.');
    }

    public function test_a_grant_stacks_on_top_of_the_default(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertSame(90, $restaurant->dish_limit, '40 default + 50 granted.');
    }

    public function test_several_grants_of_the_same_feature_add_up(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        FeatureGrant::factory()->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)->create(['reference' => 'first']);
        FeatureGrant::factory()->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)->create(['reference' => 'second']);

        $this->assertSame(140, $restaurant->dish_limit, '40 default + 50 + 50.');
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
        $this->assertSame(40, $restaurant->dish_limit);
    }

    public function test_deleting_a_grant_takes_the_allowance_away_again(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $grant = FeatureGrant::factory()
            ->for($restaurant)
            ->forFeature(Feature::DishLimit, 50)
            ->create();

        $this->assertSame(90, $restaurant->dish_limit);

        $grant->delete();

        $this->assertSame(40, $restaurant->fresh()->dish_limit, 'The cache is flushed on delete.');
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

        $this->assertSame(500, $restaurant->dish_limit);
        $this->assertSame(8, $restaurant->category_limit, 'Feature::defaultValue() fills the gap.');
        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));
    }

    public function test_templates_grant_nothing(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertFalse($restaurant->entitlements()->can(Feature::QrStudio));
    }

    public function test_a_new_restaurant_starts_on_the_default_package(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->assertSame(Package::default()->id, $restaurant->package_id);
        $this->assertNotNull($restaurant->package_started_at);
        $this->assertNull($restaurant->package_ends_at);
    }
}
