<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Schemas\FeatureFields;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Services\Packages\RestaurantFeatures;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The restaurant page's Features section: what the restaurant has, like a
 * package, switched and saved as its own (RestaurantFeatures).
 */
class RestaurantFeaturesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_it_opens_on_what_the_restaurant_has_and_what_its_package_gives(): void
    {
        $restaurant = $this->ownerOn('premium');

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSchemaStateSet([
                'features.offers' => 1,
                'features.offer_popup_limit' => 1,
                'features.dish_limit' => 1000,
            ])
            ->assertSee('Package: on')
            ->assertSee('Package: 3');
    }

    public function test_switching_off_and_changing_a_number_is_this_restaurants_own(): void
    {
        $restaurant = $this->ownerOn('premium');

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['features.offer_popup_limit' => 0, 'features.order_tracking' => false, 'features.dish_limit' => 2000])
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSee('Changed for this restaurant (package: 1)');

        $entitlements = $restaurant->fresh()->entitlements();
        $this->assertSame(0, $entitlements->limit(Feature::OfferPopupLimit));
        $this->assertFalse($entitlements->can(Feature::OrderTracking));
        $this->assertSame(2000, $entitlements->limit(Feature::DishLimit));
        // Only what was changed: three lasting set grants, nothing else.
        $this->assertSame(3, FeatureGrant::query()->where('restaurant_id', $restaurant->id)->where('mode', GrantMode::Set->value)->whereNull('ends_at')->count());
    }

    public function test_back_to_the_package_value_follows_the_package_again(): void
    {
        $restaurant = $this->ownerOn('premium');
        RestaurantFeatures::save($restaurant, ['offer_banner_limit' => 5]);
        $this->assertSame(5, $restaurant->fresh()->entitlements()->limit(Feature::OfferBannerLimit));

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['features.offer_banner_limit' => 3])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, FeatureGrant::query()->where('restaurant_id', $restaurant->id)->count());
        $this->assertSame(3, $restaurant->fresh()->entitlements()->limit(Feature::OfferBannerLimit));
    }

    public function test_a_number_left_empty_is_unlimited(): void
    {
        $restaurant = $this->ownerOn('premium');

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['features.offer_banner_limit' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($restaurant->fresh()->entitlements()->limit(Feature::OfferBannerLimit));
    }

    public function test_a_new_package_in_the_same_save_is_not_pinned_to_the_old_one(): void
    {
        $restaurant = $this->owner();

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['package_id' => Package::findBySlug('premium')->id, 'features.social_link_limit' => 4])
            ->call('save')
            ->assertHasNoFormErrors();

        $entitlements = $restaurant->fresh()->entitlements();
        // The upgrade stands; only what the admin changed is their own.
        $this->assertSame(1000, $entitlements->limit(Feature::DishLimit));
        $this->assertTrue($entitlements->can(Feature::Offers));
        $this->assertSame(4, $entitlements->limit(Feature::SocialLinkLimit));
        $this->assertSame(1, FeatureGrant::query()->where('restaurant_id', $restaurant->id)->count());
    }

    public function test_a_save_that_changes_nothing_writes_no_grant_and_keeps_a_timed_extra(): void
    {
        $restaurant = $this->owner();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 10)->create(['ends_at' => now()->addMonth()]);

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, FeatureGrant::query()->where('restaurant_id', $restaurant->id)->count());
        $this->assertSame(50, $restaurant->fresh()->entitlements()->limit(Feature::DishLimit));
    }

    public function test_every_feature_has_its_place_in_the_section_once(): void
    {
        $listed = array_merge(...array_values(FeatureFields::GROUPS));

        $this->assertEqualsCanonicalizing(Feature::cases(), $listed);
        $this->assertCount(count(Feature::cases()), $listed);
    }
}
