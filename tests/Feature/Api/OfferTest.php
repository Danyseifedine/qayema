<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Enums\MenuEventType;
use App\Enums\OfferPlacement;
use App\Models\Dish;
use App\Models\FeatureGrant;
use App\Models\MenuEvent;
use App\Models\Offer;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The owner's offers API (Premium): creating each kind, the schedule's
 * rules, the discount's dishes, pausing, reordering and deleting, and the
 * package and ownership walls.
 */
class OfferTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function premium(array $restaurant = []): Restaurant
    {
        return $this->ownerOn('premium', ['timezone' => 'Asia/Beirut', ...$restaurant]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => ['en' => 'Happy hour'],
            'description' => ['en' => 'Two drinks for one'],
            'placements' => ['banner'],
            'action' => 'none',
            'show_again' => 'every_visit',
            'delay_seconds' => 0,
            'ad_seconds' => 6,
            'discount_type' => 'none',
            'repeat' => 'always',
            ...$overrides,
        ];
    }

    public function test_guests_and_owners_without_the_package_are_refused(): void
    {
        $this->getJson(route('api.offers.index'))->assertUnauthorized();

        $pro = $this->ownerOn('pro');
        $this->actingAs($pro->user)->getJson(route('api.offers.index'))
            ->assertForbidden()
            ->assertJsonPath('message', 'Offers are not on your package.');
        $this->actingAs($pro->user)->postJson(route('api.offers.store'), $this->payload())->assertForbidden();
    }

    public function test_an_owner_creates_a_banner_offer(): void
    {
        $restaurant = $this->premium();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload())
            ->assertCreated()
            ->assertJsonPath('data.title.en', 'Happy hour')
            ->assertJsonPath('data.placements', ['banner'])
            // Shaped as most are, unless the owner picks otherwise.
            ->assertJsonPath('data.banner_shape', 'rectangle')
            ->assertJsonPath('data.popup_shape', 'square')
            ->assertJsonPath('data.status', 'live')
            ->assertJsonPath('data.is_active', true);

        $this->assertSame(1, $restaurant->offers()->count());
    }

    public function test_a_weekly_evening_discount_on_chosen_dishes(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00', 'Asia/Beirut'));
        $restaurant = $this->premium();
        $dishes = Dish::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);

        $response = $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'placements' => ['banner', 'ad'],
            'action' => 'dishes',
            'ad_seconds' => 8,
            'show_again' => 'daily',
            'discount_type' => 'percent',
            'discount_value' => 20,
            'dish_ids' => $dishes->pluck('id')->all(),
            'starts_at' => '2026-11-01T00:00',
            'ends_at' => '2026-12-01T00:00',
            'repeat' => 'weekly',
            'weekdays' => ['fri', 'sat'],
            'time_windows' => [['from' => '18:00', 'to' => '22:00']],
        ]))->assertCreated();

        // Tuesday morning: next is Friday 18:00 local.
        $response->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.next_start', '2026-11-13T18:00')
            ->assertJsonPath('data.starts_at', '2026-11-01T00:00')
            ->assertJsonPath('data.weekdays', ['fri', 'sat'])
            ->assertJsonPath('data.month_days', [])
            ->assertJsonPath('data.discount_value', '20.00');
        $this->assertEqualsCanonicalizing($dishes->pluck('id')->all(), $response->json('data.dish_ids'));

        // Stored in UTC: midnight in Beirut (UTC+2) is 22:00 the day before.
        $this->assertSame('2026-10-31 22:00:00', Offer::query()->first()->starts_at->utc()->toDateTimeString());
        Carbon::setTestNow();
    }

    public function test_an_offer_takes_a_big_sharp_picture_with_its_versions_and_size(): void
    {
        Storage::fake(config('media-library.disk_name'));
        $restaurant = $this->premium();
        // The offer preset keeps up to 2000px, so words drawn in stay sharp.
        $key = $this->actingAs($restaurant->user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('offer.jpg', 3000, 1500), 'context' => 'offer'],
            ['Accept' => 'application/json'],
        )->assertOk()->json('key');

        $response = $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['image_key' => $key]))
            ->assertCreated();

        $media = Offer::query()->firstOrFail()->getFirstMedia('image');
        $this->assertNotNull($response->json('data.image_url'));
        $this->assertTrue($media->hasGeneratedConversion('thumb'));
        $this->assertTrue($media->hasGeneratedConversion('wide'));
        $this->assertTrue($media->hasGeneratedConversion('large'));
        // Its size kept, so the menu fits it without downloading it.
        $this->assertSame([2000, 1000], [$media->getCustomProperty('width'), $media->getCustomProperty('height')]);
        $this->assertSame(2.0, Offer::query()->firstOrFail()->pictureRatio());

        // And taken away again.
        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.update', Offer::query()->firstOrFail()), $this->payload(['delete_image' => true]))
            ->assertOk()
            ->assertJsonPath('data.image_url', null);
    }

    public function test_an_offer_can_be_a_picture_alone_but_never_nothing(): void
    {
        Storage::fake(config('media-library.disk_name'));
        $restaurant = $this->premium();

        // No words and no picture: nothing to show.
        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['show_text' => false, 'title' => ['en' => '']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_key' => 'An offer without text needs a picture.'])
            ->assertJsonMissingValidationErrors(['title.en']);

        $key = $this->actingAs($restaurant->user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('promo.jpg', 1200, 800), 'context' => 'generic'],
            ['Accept' => 'application/json'],
        )->assertOk()->json('key');

        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['show_text' => false, 'title' => ['en' => ''], 'image_key' => $key]))
            ->assertCreated()
            ->assertJsonPath('data.show_text', false)
            ->assertJsonPath('data.title.en', null);
        $offer = Offer::query()->firstOrFail();

        // Edited without a new picture: the one it has counts.
        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.update', $offer), $this->payload(['show_text' => false, 'title' => ['en' => '']]))
            ->assertOk();

        // Taking the picture away from a picture alone is refused.
        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.update', $offer), $this->payload(['show_text' => false, 'title' => ['en' => ''], 'delete_image' => true]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['image_key']);
    }

    public function test_the_rules_an_offer_must_follow(): void
    {
        $restaurant = $this->premium(['main_locale' => 'en']);
        $foreign = Dish::factory()->create();

        $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'title' => ['en' => ''],
            'placements' => ['popup', 'carousel'],
            'action' => 'link',
            'action_url' => 'http://example.com',
            'discount_type' => 'percent',
            'discount_value' => 95,
            'dish_ids' => [$foreign->id],
        ]))->assertUnprocessable()->assertJsonValidationErrors([
            'title.en', 'placements.1', 'action_url', 'discount_value', 'dish_ids.0',
        ]);

        $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'discount_type' => 'amount',
            'discount_value' => 2,
        ]))->assertUnprocessable()->assertJsonValidationErrors(['dish_ids']);
    }

    public function test_the_owner_picks_a_banner_and_a_pop_up_shape(): void
    {
        $restaurant = $this->premium();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['placements' => ['banner', 'popup'], 'banner_shape' => 'square', 'popup_shape' => 'tall']))
            ->assertCreated()
            ->assertJsonPath('data.banner_shape', 'square')
            ->assertJsonPath('data.popup_shape', 'tall');

        // An ad has no shape to pick, and a banner is never tall.
        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['banner_shape' => 'tall', 'popup_shape' => 'rectangle']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['banner_shape', 'popup_shape']);
    }

    public function test_an_offer_may_be_a_banner_a_pop_up_and_an_ad_but_there_is_no_section(): void
    {
        $restaurant = $this->premium();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['placements' => ['section']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['placements.0']);

        // All three: the ad waits its own seconds after the pop-up closes.
        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['placements' => ['banner', 'popup', 'ad'], 'delay_seconds' => 1, 'ad_delay_seconds' => 5]))
            ->assertCreated()
            ->assertJsonPath('data.placements', ['banner', 'popup', 'ad'])
            ->assertJsonPath('data.delay_seconds', 1)
            ->assertJsonPath('data.ad_delay_seconds', 5);
    }

    public function test_each_place_holds_as_many_switched_on_offers_as_the_package_allows(): void
    {
        $restaurant = $this->premium();
        $store = fn (array $overrides) => $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload($overrides));

        // Premium: one pop-up switched on at a time.
        $first = $store(['placements' => ['popup']])->assertCreated()->json('data.id');
        $store(['placements' => ['popup']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['placements' => 'Your package allows 1 pop-up offers switched on at a time. Pause or end one first.']);

        // A paused one takes no slot, but switching it on would make two.
        $paused = $store(['placements' => ['popup'], 'is_active' => false])->assertCreated()->json('data.id');
        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.active', $paused), ['is_active' => true])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['is_active']);

        $this->actingAs($restaurant->user)->getJson(route('api.offers.index'))
            ->assertJsonPath('meta.slots.popup', ['used' => 1, 'limit' => 1])
            ->assertJsonPath('meta.slots.banner', ['used' => 0, 'limit' => 3])
            ->assertJsonPath('meta.slots.ad', ['used' => 0, 'limit' => 1]);

        // Editing the one holding the slot keeps it.
        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.update', $first), $this->payload(['placements' => ['popup'], 'title' => ['en' => 'Renamed']]))
            ->assertOk();

        // One past its end frees its slot.
        Offer::query()->whereKey($first)->update(['ends_at' => now()->subMinute()]);
        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.active', $paused), ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.is_active', true);
    }

    public function test_the_admin_can_give_one_restaurant_more_of_a_place(): void
    {
        $restaurant = $this->premium();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::OfferPopupLimit, 2)->create(['mode' => GrantMode::Set]);

        foreach ([1, 2] as $count) {
            $this->actingAs($restaurant->user)
                ->postJson(route('api.offers.store'), $this->payload(['placements' => ['popup']]))
                ->assertCreated();
        }

        $this->actingAs($restaurant->user)->getJson(route('api.offers.index'))
            ->assertJsonPath('meta.slots.popup', ['used' => 2, 'limit' => 2]);
    }

    public function test_only_the_parts_of_offers_on_the_package_can_be_saved(): void
    {
        $restaurant = $this->premium();
        foreach ([Feature::OfferPopupLimit, Feature::OfferSchedules, Feature::OfferDiscounts] as $part) {
            FeatureGrant::factory()->for($restaurant)->forFeature($part, 0)->create(['mode' => GrantMode::Set]);
        }
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'price' => '12.00']);
        $store = fn (array $overrides) => $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload($overrides));

        // A pop-up is refused even paused: the place is not on the package.
        $store(['placements' => ['banner', 'popup'], 'is_active' => false])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['placements' => 'Pop-ups are not on your package.']);
        $store(['repeat' => 'weekly', 'weekdays' => ['sun']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['repeat' => 'Schedules are not on your package.']);
        $store(['ends_at' => '2026-12-01T00:00'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['repeat']);
        $store(['discount_type' => 'percent', 'discount_value' => 10, 'dish_ids' => [$dish->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_type' => 'Discounts are not on your package.']);

        // A banner, always on, without a discount is fine.
        $store([])->assertCreated();
        $this->actingAs($restaurant->user)->getJson(route('api.offers.index'))
            ->assertJsonPath('meta.slots.popup.limit', 0);
    }

    public function test_an_offer_the_package_no_longer_runs_is_off_and_says_why(): void
    {
        $restaurant = $this->premium();
        $weekly = Offer::factory()->create(['restaurant_id' => $restaurant->id, 'repeat' => 'weekly', 'weekdays' => ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun']]);
        $popup = Offer::factory()->in(OfferPlacement::Popup)->create(['restaurant_id' => $restaurant->id]);
        $paused = Offer::factory()->paused()->in(OfferPlacement::Popup)->create(['restaurant_id' => $restaurant->id]);
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::OfferSchedules, 0)->create(['mode' => GrantMode::Set]);
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::OfferPopupLimit, 0)->create(['mode' => GrantMode::Set]);

        $data = collect($this->actingAs($restaurant->user)->getJson(route('api.offers.index'))->assertOk()->json('data'))->keyBy('id');

        $this->assertSame(['off', 'schedules'], [$data[$weekly->id]['status'], $data[$weekly->id]['kept_off']]);
        $this->assertSame(['off', 'places'], [$data[$popup->id]['status'], $data[$popup->id]['kept_off']]);
        // Paused says paused, with the reason kept for when it is switched on.
        $this->assertSame(['paused', 'places'], [$data[$paused->id]['status'], $data[$paused->id]['kept_off']]);

        // Switching it on is not refused for a place it takes no slot in.
        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.active', $paused), ['is_active' => true])
            ->assertOk()
            ->assertJsonPath('data.status', 'off');
    }

    public function test_an_amount_off_stays_below_the_cheapest_dish(): void
    {
        $restaurant = $this->premium(['currency' => 'LBP']);
        $mezze = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Hummus'], 'price' => '300000.00']);
        $grill = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'price' => '900000.00']);
        $byChoice = Dish::factory()->create(['restaurant_id' => $restaurant->id, 'price' => null]);
        $offer = fn (string $value) => $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'discount_type' => 'amount',
            'discount_value' => $value,
            'dish_ids' => [$mezze->id, $grill->id, $byChoice->id],
        ]));

        $offer('300000')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_value' => 'Hummus costs L.L 300,000: a discount must take off less than that.']);

        // A big currency has room: 150,000 off is fine.
        $offer('150000')->assertCreated()->assertJsonPath('data.discount_value', '150000.00');
    }

    public function test_an_amount_off_stays_below_a_dish_priced_by_its_sizes(): void
    {
        $restaurant = $this->premium(['currency' => 'USD']);
        $pizza = Dish::factory()->withVariants(['Size' => ['Small' => 6, 'Large' => 12]])->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Pizza'], 'price' => null]);
        $offer = fn (string $value) => $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'discount_type' => 'amount',
            'discount_value' => $value,
            'dish_ids' => [$pizza->id],
        ]));

        // Its smallest size is the least it costs.
        $offer('6')
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['discount_value' => 'Pizza costs $6: a discount must take off less than that.']);
        $offer('5')->assertCreated();
    }

    public function test_a_pair_deal_takes_up_to_all_of_the_second_dish(): void
    {
        $restaurant = $this->premium();
        $dish = Dish::factory()->create(['restaurant_id' => $restaurant->id]);
        $offer = fn (int $value) => $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'discount_type' => 'second',
            'discount_value' => $value,
            'dish_ids' => [$dish->id],
        ]));

        $offer(101)->assertJsonValidationErrors(['discount_value' => 'A discount can take off at most 100%.']);
        $offer(100)
            ->assertCreated()
            ->assertJsonPath('data.discount_type', 'second')
            ->assertJsonPath('data.discount_value', '100.00');
        $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload(['discount_type' => 'second', 'discount_value' => 50]))
            ->assertJsonValidationErrors(['dish_ids']);
    }

    public function test_it_shows_outside_its_hours_only_when_it_has_hours(): void
    {
        $restaurant = $this->premium();
        $store = fn (array $overrides) => $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload($overrides));

        $store(['repeat' => 'weekly', 'weekdays' => ['wed'], 'time_windows' => [['from' => '17:00', 'to' => '19:00']], 'shows_outside_hours' => true])
            ->assertCreated()
            ->assertJsonPath('data.shows_outside_hours', true);
        // Dates alone have no hours to be outside of.
        $store(['ends_at' => '2030-01-01T00:00', 'shows_outside_hours' => true])
            ->assertCreated()
            ->assertJsonPath('data.shows_outside_hours', false);
        $store(['shows_outside_hours' => false])->assertCreated()->assertJsonPath('data.shows_outside_hours', false);
        $store(['shows_outside_hours' => 'often'])->assertJsonValidationErrors(['shows_outside_hours']);
    }

    public function test_the_schedule_is_checked_whole(): void
    {
        $restaurant = $this->premium();

        $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'starts_at' => '2026-11-10T10:00',
            'ends_at' => '2026-11-09T10:00',
            'repeat' => 'monthly',
            'month_days' => [],
            'time_windows' => [['from' => '22:00', 'to' => '02:00'], ['from' => '23:00', 'to' => '23:30']],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['ends_at', 'month_days', 'time_windows']);

        $this->actingAs($restaurant->user)->postJson(route('api.offers.store'), $this->payload([
            'starts_at' => '10 November',
            'time_windows' => [['from' => '25:00', 'to' => '26:00']],
            'weekdays' => ['funday'],
        ]))->assertUnprocessable()->assertJsonValidationErrors(['starts_at', 'time_windows.0.from', 'weekdays.0']);
    }

    public function test_editing_replaces_the_offer_and_its_dishes(): void
    {
        $restaurant = $this->premium();
        [$kept, $dropped, $added] = Dish::factory()->count(3)->create(['restaurant_id' => $restaurant->id])->all();
        $offer = Offer::factory()->percentOff(10)->create(['restaurant_id' => $restaurant->id]);
        $offer->dishes()->sync([$kept->id, $dropped->id]);

        $this->actingAs($restaurant->user)->patchJson(route('api.offers.update', $offer), $this->payload([
            'title' => ['en' => 'Lunch deal'],
            'placements' => ['popup'],
            'discount_type' => 'amount',
            'discount_value' => 1.5,
            'dish_ids' => [$kept->id, $added->id],
            'repeat' => 'weekly',
            'weekdays' => ['mon'],
        ]))->assertOk()
            ->assertJsonPath('data.title.en', 'Lunch deal')
            ->assertJsonPath('data.placements', ['popup'])
            ->assertJsonPath('data.discount_type', 'amount');

        $this->assertEqualsCanonicalizing([$kept->id, $added->id], $offer->dishes()->pluck('dishes.id')->all());

        // Back to every day: the weekdays are not kept behind.
        $this->actingAs($restaurant->user)->patchJson(route('api.offers.update', $offer), $this->payload())->assertOk()
            ->assertJsonPath('data.weekdays', [])
            ->assertJsonPath('data.dish_ids', [])
            ->assertJsonPath('data.discount_value', null);
    }

    public function test_pausing_and_resuming(): void
    {
        $restaurant = $this->premium();
        $offer = Offer::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($restaurant->user)
            ->patchJson(route('api.offers.active', $offer), ['is_active' => false])
            ->assertOk()
            ->assertJsonPath('data.status', 'paused');

        $this->assertFalse($offer->fresh()->is_active);
    }

    public function test_another_restaurants_offer_is_out_of_reach(): void
    {
        $restaurant = $this->premium();
        $theirs = Offer::factory()->create(['restaurant_id' => $this->premium()->id]);

        $this->actingAs($restaurant->user)->patchJson(route('api.offers.update', $theirs), $this->payload())->assertForbidden();
        $this->actingAs($restaurant->user)->patchJson(route('api.offers.active', $theirs), ['is_active' => false])->assertForbidden();
        $this->actingAs($restaurant->user)->deleteJson(route('api.offers.destroy', $theirs))->assertForbidden();
        $this->assertModelExists($theirs);
    }

    public function test_reordering_and_deleting(): void
    {
        $restaurant = $this->premium();
        [$first, $second] = Offer::factory()->count(2)->sequence(['display_order' => 1], ['display_order' => 2])->create(['restaurant_id' => $restaurant->id])->all();
        $theirs = Offer::factory()->create(['restaurant_id' => $this->premium()->id, 'display_order' => 9]);

        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.reorder'), ['ids' => [$second->id, $first->id, $theirs->id]])
            ->assertOk()
            ->assertJsonPath('data.0.id', $second->id);
        $this->assertSame(9, $theirs->fresh()->display_order);

        $this->actingAs($restaurant->user)->deleteJson(route('api.offers.destroy', $first))->assertNoContent();
        $this->assertModelMissing($first);
    }

    public function test_the_list_counts_how_often_each_offer_was_shown_and_tapped(): void
    {
        $restaurant = $this->premium();
        $offer = Offer::factory()->in(OfferPlacement::Popup)->create(['restaurant_id' => $restaurant->id]);
        $event = fn (MenuEventType $type, $when = null) => MenuEvent::query()->insert([
            'restaurant_id' => $restaurant->id,
            'session_id' => 'guest',
            'type' => $type->value,
            'value' => (string) $offer->id,
            'occurred_at' => $when ?? now(),
        ]);
        $event(MenuEventType::OfferShown);
        $event(MenuEventType::OfferShown);
        $event(MenuEventType::OfferTap);
        $event(MenuEventType::OfferShown, now()->subDays(40));

        $this->actingAs($restaurant->user)->getJson(route('api.offers.index'))
            ->assertOk()
            ->assertJsonPath('meta.stats.'.$offer->id, ['shown' => 2, 'taps' => 1])
            ->assertJsonPath('meta.limit', Offer::MAX_PER_RESTAURANT)
            ->assertJsonPath('meta.timezone', 'Asia/Beirut');
    }

    public function test_a_menu_holds_at_most_thirty_offers(): void
    {
        $restaurant = $this->premium();
        Offer::factory()->count(Offer::MAX_PER_RESTAURANT)->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload())
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['title']);
    }

    public function test_deleting_the_restaurant_takes_its_offers(): void
    {
        $restaurant = $this->premium();
        $offer = Offer::factory()->create(['restaurant_id' => $restaurant->id]);

        $restaurant->delete();

        $this->assertModelMissing($offer);
    }
}
