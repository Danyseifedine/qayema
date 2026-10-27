<?php

namespace Tests\Integration\Services\Offers;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Enums\OfferPlacement;
use App\Enums\OfferRepeat;
use App\Models\Dish;
use App\Models\FeatureGrant;
use App\Models\Offer;
use App\Models\Restaurant;
use App\Services\Offers\LiveOffers;
use App\Services\Orders\OrderPlacer;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Which offers the menu carries and what the server charges for a dish on
 * one: only with the `offers` flag, only while live, the better deal when
 * two cover a dish, and the order line keeping the offer.
 */
class LiveOffersTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function premium(): Restaurant
    {
        return $this->ownerOn('premium', ['timezone' => 'Asia/Beirut', 'currency' => 'USD']);
    }

    private function dish(Restaurant $restaurant, string $price = '12.50'): Dish
    {
        return Dish::factory()->for($restaurant)->create(['name' => ['en' => 'Kafta'], 'price' => $price]);
    }

    private function offerOn(Dish $dish, array $state = [], ?float $percent = 20): Offer
    {
        $offer = Offer::factory()->percentOff($percent)->create(['restaurant_id' => $dish->restaurant_id, ...$state]);
        $offer->dishes()->attach($dish);

        return $offer;
    }

    public function test_the_menu_carries_running_offers_and_not_paused_or_ended_ones(): void
    {
        $restaurant = $this->premium();
        $running = Offer::factory()->create(['restaurant_id' => $restaurant->id]);
        // Not live yet, but the phone shows it when its time comes.
        $later = Offer::factory()->create(['restaurant_id' => $restaurant->id, 'starts_at' => now()->addDay()]);
        $paused = Offer::factory()->paused()->create(['restaurant_id' => $restaurant->id]);
        $ended = Offer::factory()->create(['restaurant_id' => $restaurant->id, 'ends_at' => now()->subMinute()]);

        $ids = app(LiveOffers::class)->forMenu($restaurant)->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$running->id, $later->id], $ids);

        // The owner's preview of one adds it whatever its state.
        $this->assertContains($paused->id, app(LiveOffers::class)->forMenu($restaurant, $paused->id)->pluck('id')->all());
        $this->assertNotContains($ended->id, app(LiveOffers::class)->forMenu($restaurant, $paused->id)->pluck('id')->all());
    }

    public function test_the_live_ones_among_offers_decide_what_deal_dishes_are_sold(): void
    {
        $restaurant = $this->premium();
        $now = CarbonImmutable::now();
        $live = Offer::factory()->create(['restaurant_id' => $restaurant->id]);
        $later = Offer::factory()->create(['restaurant_id' => $restaurant->id, 'starts_at' => $now->addDay()]);
        $paused = Offer::factory()->paused()->create(['restaurant_id' => $restaurant->id]);
        $ended = Offer::factory()->create(['restaurant_id' => $restaurant->id, 'ends_at' => $now->subMinute()]);
        $theirs = Offer::factory()->create();
        $offers = app(LiveOffers::class);

        $this->assertSame([$live->id], $offers->liveAmong($restaurant, [$live->id, $later->id, $paused->id, $ended->id, $theirs->id], $now));
        $this->assertSame([], $offers->liveAmong($restaurant, [], $now));

        // A schedule the package no longer runs keeps it off, as on the menu.
        $scheduled = Offer::factory()->create(['restaurant_id' => $restaurant->id, 'repeat' => OfferRepeat::Weekly, 'weekdays' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']]);
        $this->assertSame([$scheduled->id], $offers->liveAmong($restaurant, [$scheduled->id], $now));
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::OfferSchedules, 0)->create(['mode' => GrantMode::Set]);
        $this->assertSame([], $offers->liveAmong($restaurant->fresh(), [$scheduled->id], $now));

        $this->assertSame([], $offers->liveAmong($this->ownerOn('pro'), [$live->id], $now));
    }

    public function test_nothing_without_the_package(): void
    {
        $restaurant = $this->ownerOn('pro');
        $dish = $this->dish($restaurant);
        $this->offerOn($dish);

        $this->assertCount(0, app(LiveOffers::class)->forMenu($restaurant));
        $this->assertSame([], app(LiveOffers::class)->bestFor($restaurant, [$dish->id], [$dish->id => '12.50'], CarbonImmutable::now()));
    }

    /** The admin set one part of offers for this restaurant (0 leaves it out). */
    private function sets(Restaurant $restaurant, Feature $feature, int $value): void
    {
        FeatureGrant::factory()->for($restaurant)->forFeature($feature, $value)->create(['mode' => GrantMode::Set]);
    }

    public function test_without_discounts_on_the_package_an_offer_is_a_promotion_and_the_order_pays_full_price(): void
    {
        $restaurant = $this->premium();
        $this->sets($restaurant, Feature::OfferDiscounts, 0);
        $dish = $this->dish($restaurant);
        $offer = $this->offerOn($dish);

        $shown = app(LiveOffers::class)->forMenu($restaurant);
        $this->assertSame([$offer->id], $shown->pluck('id')->all());
        $this->assertFalse($shown->first()->hasDiscount());

        $line = app(OrderPlacer::class)->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 1]])->items->first();
        $this->assertSame('12.50', $line->unit_price);
        $this->assertNull($line->offer_id);
    }

    public function test_without_schedules_on_the_package_a_scheduled_offer_stays_off(): void
    {
        $restaurant = $this->premium();
        $this->sets($restaurant, Feature::OfferSchedules, 0);
        $dish = $this->dish($restaurant);
        $always = Offer::factory()->create(['restaurant_id' => $restaurant->id]);
        $weekly = $this->offerOn($dish, ['repeat' => OfferRepeat::Weekly, 'weekdays' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']]);

        $this->assertSame([$always->id], app(LiveOffers::class)->forMenu($restaurant)->pluck('id')->all());
        // Live by its days, but its package runs no schedule: nothing off.
        $this->assertSame([], app(LiveOffers::class)->bestFor($restaurant, [$dish->id], [$dish->id => '12.50'], CarbonImmutable::now()));
        $this->assertTrue($weekly->hasSchedule());
        $this->assertFalse($always->hasSchedule());
    }

    public function test_a_place_off_the_package_is_left_out_and_an_offer_with_no_place_left_gives_nothing(): void
    {
        $restaurant = $this->premium();
        $this->sets($restaurant, Feature::OfferPopupLimit, 0);
        $dish = $this->dish($restaurant);
        $both = Offer::factory()->in(OfferPlacement::Banner, OfferPlacement::Popup)->create(['restaurant_id' => $restaurant->id]);
        $popupOnly = $this->offerOn($dish, ['placements' => [OfferPlacement::Popup->value]]);

        $shown = app(LiveOffers::class)->forMenu($restaurant);
        $this->assertSame([$both->id], $shown->pluck('id')->all());
        $this->assertSame([OfferPlacement::Banner], $shown->first()->placementList());
        // Never saved that way: the offer keeps both places.
        $this->assertSame(['banner', 'popup'], $both->fresh()->placements);
        $this->assertSame([], app(LiveOffers::class)->bestFor($restaurant, [$dish->id], [$dish->id => '12.50'], CarbonImmutable::now()));
        $this->assertNotNull($popupOnly->id);
    }

    public function test_an_order_pays_the_discounted_price_and_keeps_the_offer(): void
    {
        $restaurant = $this->premium();
        $dish = $this->dish($restaurant);
        $offer = $this->offerOn($dish, ['title' => ['en' => 'Kafta week']]);

        $order = app(OrderPlacer::class)->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 2]]);
        $line = $order->items->first();

        $this->assertSame('10.00', $line->unit_price);
        $this->assertSame('20.00', $line->line_total);
        $this->assertSame('12.50', $line->full_price);
        $this->assertSame($offer->id, $line->offer_id);
        $this->assertSame('Kafta week', $line->offer_title);
        $this->assertSame('20.00', $order->fresh()->total);
    }

    public function test_outside_its_hours_the_dish_costs_its_own_price(): void
    {
        // Tuesday 10:00 in Beirut; the offer runs Fridays only.
        $this->travelTo(CarbonImmutable::parse('2026-11-10 10:00', 'Asia/Beirut'));
        $restaurant = $this->premium();
        $dish = $this->dish($restaurant);
        $this->offerOn($dish, ['repeat' => OfferRepeat::Weekly, 'weekdays' => ['fri']]);

        $line = app(OrderPlacer::class)->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 1]])->items->first();

        $this->assertSame('12.50', $line->unit_price);
        $this->assertNull($line->offer_id);
        $this->assertNull($line->full_price);
    }

    public function test_paused_or_not_started_offers_give_nothing(): void
    {
        $restaurant = $this->premium();
        $dish = $this->dish($restaurant);
        $this->offerOn($dish, ['is_active' => false]);
        $this->offerOn($dish, ['starts_at' => now()->addHour()]);

        $this->assertSame([], app(LiveOffers::class)->bestFor($restaurant, [$dish->id], [$dish->id => '12.50'], CarbonImmutable::now()));
    }

    public function test_the_guest_gets_the_better_of_two_offers(): void
    {
        $restaurant = $this->premium();
        $dish = $this->dish($restaurant, '10.00');
        $this->offerOn($dish, [], 10);
        $better = Offer::factory()->amountOff(3)->create(['restaurant_id' => $restaurant->id]);
        $better->dishes()->attach($dish);

        $best = app(LiveOffers::class)->bestFor($restaurant, [$dish->id], [$dish->id => '10.00'], CarbonImmutable::now());

        $this->assertSame($better->id, $best[$dish->id]->id);
    }

    public function test_choices_keep_their_price_and_count_in_the_full_price(): void
    {
        $restaurant = $this->premium();
        $dish = Dish::factory()->withVariants(['Size' => ['Small' => 0, 'Large' => 3]])->for($restaurant)->create(['price' => '10.00']);
        $this->offerOn($dish, [], 50);
        $large = $dish->load('variants.options')->variants->first()->options->last();

        $line = app(OrderPlacer::class)->place($restaurant, [['dish_id' => $dish->id, 'quantity' => 1, 'options' => [$large->id]]])->items->first();

        // Half of the dish's 10.00, and the 3.00 for Large as it is.
        $this->assertSame('8.00', $line->unit_price);
        $this->assertSame('13.00', $line->full_price);
    }
}
