<?php

namespace Tests\Integration\Services\Offers;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Enums\OfferPlacement;
use App\Models\FeatureGrant;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Services\Offers\OfferSlots;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Services\Offers\OfferSlots on its own: what takes a slot, the limits
 * that refuse nothing (unlimited, and a place the package leaves out), and
 * the list's counts read from offers already in hand.
 */
class OfferSlotsTest extends TestCase
{
    use CreatesOwners;
    use RefreshDatabase;

    private function premium(): Restaurant
    {
        return $this->ownerOn('premium');
    }

    private function setLimit(Restaurant $restaurant, Feature $feature, ?int $value): void
    {
        FeatureGrant::factory()->for($restaurant)->create(['feature' => $feature, 'mode' => GrantMode::Set, 'value' => $value]);
        $restaurant->unsetRelation('featureGrants');
    }

    public function test_only_a_switched_on_offer_that_has_not_ended_takes_a_slot(): void
    {
        $restaurant = $this->premium();

        $this->assertTrue(OfferSlots::takesSlots(Offer::factory()->make(['restaurant_id' => $restaurant->id])));
        $this->assertTrue(OfferSlots::takesSlots(Offer::factory()->make(['ends_at' => now()->addMinute()])));
        $this->assertFalse(OfferSlots::takesSlots(Offer::factory()->paused()->make()));
        $this->assertFalse(OfferSlots::takesSlots(Offer::factory()->make(['ends_at' => now()->subSecond()])));
    }

    public function test_an_unlimited_place_never_refuses(): void
    {
        $restaurant = $this->premium();
        $this->setLimit($restaurant, Feature::OfferPopupLimit, null);
        Offer::factory()->count(4)->in(OfferPlacement::Popup)->create(['restaurant_id' => $restaurant->id]);

        OfferSlots::ensureRoomFor(Offer::factory()->in(OfferPlacement::Popup)->make(['restaurant_id' => $restaurant->id]), $restaurant, 'placements');

        $this->assertSame(['used' => 4, 'limit' => null], OfferSlots::of($restaurant)['popup']);
    }

    public function test_a_place_the_package_leaves_out_takes_no_slot_and_is_left_to_the_rules(): void
    {
        $restaurant = $this->premium();
        $this->setLimit($restaurant, Feature::OfferAdLimit, 0);
        Offer::factory()->in(OfferPlacement::Ad)->create(['restaurant_id' => $restaurant->id]);

        // No "allows 0 offers" message: OfferRules says the place is not on the package.
        OfferSlots::ensureRoomFor(Offer::factory()->in(OfferPlacement::Ad)->make(['restaurant_id' => $restaurant->id]), $restaurant, 'placements');

        $this->assertSame(0, OfferSlots::of($restaurant)['ad']['limit']);
    }

    public function test_an_offer_in_two_places_is_refused_by_the_one_that_is_full(): void
    {
        $restaurant = $this->premium();
        Offer::factory()->in(OfferPlacement::Popup)->create(['restaurant_id' => $restaurant->id]);
        $both = Offer::factory()->in(OfferPlacement::Banner, OfferPlacement::Popup)->make(['restaurant_id' => $restaurant->id]);

        try {
            OfferSlots::ensureRoomFor($both, $restaurant, 'is_active');
            $this->fail('A full pop-up place let a second pop-up in.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                ['is_active' => ['Your package allows 1 pop-up offers switched on at a time. Pause or end one first.']],
                $exception->errors(),
            );
        }

        // Paused, it waits without a slot.
        OfferSlots::ensureRoomFor($both->forceFill(['is_active' => false]), $restaurant, 'is_active');
    }

    public function test_the_list_counts_from_the_offers_it_already_read_as_from_the_database(): void
    {
        $restaurant = $this->premium();
        Offer::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);
        Offer::factory()->paused()->create(['restaurant_id' => $restaurant->id]);
        Offer::factory()->in(OfferPlacement::Banner, OfferPlacement::Popup)->create(['restaurant_id' => $restaurant->id, 'ends_at' => now()->subDay()]);
        Offer::factory()->in(OfferPlacement::Ad)->create(['restaurant_id' => $restaurant->id]);

        $read = OfferSlots::of($restaurant);

        $this->assertSame($read, OfferSlots::of($restaurant, $restaurant->offers()->get()));
        $this->assertSame(['used' => 2, 'limit' => 3], $read['banner']);
        $this->assertSame(['used' => 0, 'limit' => 1], $read['popup']);
        $this->assertSame(['used' => 1, 'limit' => 1], $read['ad']);
    }
}
