<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Models\Category;
use App\Models\Dish;
use App\Models\FeatureGrant;
use App\Models\Offer;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * An offer's own dish (its deal) through the offers API: made with the
 * offer and its photo, edited, kept when left out, deleted with `deal:
 * null` or the offer, refused beside a discount or other dishes and over
 * the dish limit; and how the dishes API shows and edits it.
 */
class DealDishTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake(config('media-library.disk_name'));
        $this->restaurant = $this->ownerOn('premium', ['timezone' => 'Asia/Beirut', 'currency' => 'USD']);
        $this->category = Category::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => ['en' => 'Pizza']]);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return [
            'title' => ['en' => 'Family meal'],
            'placements' => ['banner'],
            'action' => 'dishes',
            'show_again' => 'every_visit',
            'delay_seconds' => 0,
            'ad_seconds' => 6,
            'discount_type' => 'none',
            'repeat' => 'always',
            ...$overrides,
        ];
    }

    /** @return array<string, mixed> */
    private function deal(array $overrides = []): array
    {
        return [
            'name' => ['en' => 'Family meal'],
            'ingredients' => ['en' => "2 pizzas\ndrinks"],
            'price' => 25,
            'category_id' => $this->category->id,
            ...$overrides,
        ];
    }

    private function upload(string $context = 'offer'): string
    {
        return $this->actingAs($this->restaurant->user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('picture.jpg', 1200, 600), 'context' => $context],
            ['Accept' => 'application/json'],
        )->assertOk()->json('key');
    }

    private function store(array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->restaurant->user)->postJson(route('api.offers.store'), $this->payload($overrides));
    }

    private function update(Offer $offer, array $overrides = []): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->restaurant->user)->patchJson(route('api.offers.update', $offer), $this->payload($overrides));
    }

    public function test_an_offer_makes_its_deal_dish_with_a_copy_of_its_picture(): void
    {
        $other = Dish::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $this->category->id, 'display_order' => 7]);

        $response = $this->store(['image_key' => $this->upload(), 'deal' => $this->deal()])->assertCreated();

        $offer = Offer::query()->firstOrFail();
        $deal = Dish::query()->whereKeyNot($other->id)->firstOrFail();
        $response->assertJsonPath('data.deal.id', $deal->id)
            ->assertJsonPath('data.deal.name.en', 'Family meal')
            // Tidied as a dish's are.
            ->assertJsonPath('data.deal.ingredients.en', '2 pizzas, drinks')
            ->assertJsonPath('data.deal.price', '25.00')
            ->assertJsonPath('data.deal.currency', null)
            ->assertJsonPath('data.deal.category_id', $this->category->id)
            ->assertJsonPath('data.deal.is_available', true)
            // Its one dish, so the offer's tap opens it.
            ->assertJsonPath('data.dish_ids', [$deal->id])
            ->assertJsonPath('data.action', 'dishes');

        $this->assertSame($offer->id, $deal->offer_id);
        $this->assertSame($deal->id, $offer->deal->id);
        // Last in its category.
        $this->assertSame(8, $deal->display_order);

        // The photo is a copy of the offer's picture, with the dish's own small version.
        $photo = $deal->getFirstMedia('image');
        $this->assertNotNull($photo);
        $this->assertNotSame($offer->getFirstMedia('image')->id, $photo->id);
        $this->assertTrue($photo->hasGeneratedConversion('thumb'));
        Storage::disk($photo->disk)->assertExists($photo->getPathRelativeToRoot());
        $this->assertNotNull($response->json('data.deal.image_url'));
        $this->assertStringContainsString('thumb', (string) $response->json('data.deal.thumb_url'));
    }

    public function test_a_deal_dish_takes_its_own_upload_or_none(): void
    {
        $this->store(['image_key' => $this->upload(), 'deal' => $this->deal(['image_key' => $this->upload('dish')])])->assertCreated();
        $deal = Dish::query()->firstOrFail();
        $this->assertSame(1, $deal->getMedia('image')->count());
        $this->assertNotSame(Offer::query()->firstOrFail()->getFirstMedia('image')->file_name, $deal->getFirstMedia('image')->file_name);

        // Not the offer's picture, and none of its own: no photo.
        $this->store(['image_key' => $this->upload(), 'deal' => $this->deal(['use_offer_image' => false])])->assertCreated()
            ->assertJsonPath('data.deal.image_url', null)
            ->assertJsonPath('data.deal.thumb_url', null);

        // An offer without a picture has none to give.
        $this->store(['deal' => $this->deal()])->assertCreated()->assertJsonPath('data.deal.image_url', null);
    }

    public function test_an_edit_updates_the_deal_and_copies_the_picture_only_when_it_changed(): void
    {
        $this->store(['image_key' => $this->upload(), 'deal' => $this->deal()])->assertCreated();
        $offer = Offer::query()->firstOrFail();
        $first = $offer->deal->getFirstMedia('image')->id;
        $pasta = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);

        // Same picture: the copy stays as it is; the rest is written.
        $this->update($offer, ['deal' => $this->deal(['name' => ['en' => 'Big family meal'], 'price' => '30.5', 'ingredients' => null, 'use_offer_image' => true, 'category_id' => $pasta->id])])
            ->assertOk()
            ->assertJsonPath('data.deal.name.en', 'Big family meal')
            ->assertJsonPath('data.deal.price', '30.50')
            ->assertJsonPath('data.deal.ingredients', null)
            ->assertJsonPath('data.deal.category_id', $pasta->id)
            ->assertJsonPath('data.dish_ids', [$offer->deal->id]);
        $this->assertSame($first, $offer->deal->fresh()->getFirstMedia('image')->id);
        $this->assertSame(1, Dish::query()->count());

        // A new picture for the offer: copied again.
        $this->update($offer, ['image_key' => $this->upload(), 'deal' => $this->deal(['use_offer_image' => true])])->assertOk();
        $deal = $offer->deal->fresh();
        $this->assertNotSame($first, $deal->getFirstMedia('image')->id);
        $this->assertSame(1, $deal->getMedia('image')->count());

        // Its own upload replaces the copy.
        $this->update($offer, ['deal' => $this->deal(['image_key' => $this->upload('dish')])])->assertOk();
        $this->assertSame(1, $offer->deal->fresh()->getMedia('image')->count());
    }

    public function test_left_out_the_deal_stays_and_null_deletes_it_with_its_photo(): void
    {
        $this->store(['image_key' => $this->upload(), 'deal' => $this->deal()])->assertCreated();
        $offer = Offer::query()->firstOrFail();
        $deal = $offer->deal;
        $photo = $deal->getFirstMedia('image');

        // The editor sends no `deal`: it stays, and stays the offer's one dish.
        $this->update($offer, ['dish_ids' => []])->assertOk()
            ->assertJsonPath('data.deal.id', $deal->id)
            ->assertJsonPath('data.dish_ids', [$deal->id]);

        // While it has one, no discount and no other dishes.
        $other = Dish::factory()->create(['restaurant_id' => $this->restaurant->id, 'price' => '10.00']);
        $this->update($offer, ['dish_ids' => [$other->id]])->assertUnprocessable()
            ->assertJsonValidationErrors(['deal' => 'An offer with a deal dish has no discount and no other dishes.']);

        $this->update($offer, ['deal' => null, 'action' => 'none'])->assertOk()
            ->assertJsonPath('data.deal', null)
            ->assertJsonPath('data.dish_ids', []);
        $this->assertModelMissing($deal);
        Storage::disk($photo->disk)->assertMissing($photo->getPathRelativeToRoot());
        $this->assertNotNull($offer->fresh()->getFirstMedia('image'));
    }

    public function test_the_editor_may_send_its_own_deal_back_among_its_dishes(): void
    {
        $this->store(['deal' => $this->deal()])->assertCreated();
        $offer = Offer::query()->firstOrFail();
        $dealId = $offer->deal->id;

        // As OfferResource lists it: its deal is its one dish.
        $this->update($offer, ['dish_ids' => [$dealId], 'deal' => $this->deal(['price' => 26])])->assertOk()
            ->assertJsonPath('data.dish_ids', [$dealId]);
        $this->update($offer, ['dish_ids' => [$dealId]])->assertOk()
            ->assertJsonPath('data.deal.price', '26.00');

        // Deleted in the same save: no longer one of its dishes.
        $this->update($offer, ['dish_ids' => [$dealId], 'deal' => null])->assertOk()
            ->assertJsonPath('data.deal', null)
            ->assertJsonPath('data.dish_ids', []);
    }

    public function test_a_deal_has_no_discount_and_no_other_dishes(): void
    {
        $dish = Dish::factory()->create(['restaurant_id' => $this->restaurant->id, 'price' => '20.00']);

        $this->store(['discount_type' => 'percent', 'discount_value' => 10, 'deal' => $this->deal()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deal' => 'An offer with a deal dish has no discount and no other dishes.']);

        $this->store(['dish_ids' => [$dish->id], 'deal' => $this->deal()])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deal']);

        // In Arabic too.
        $this->actingAs($this->restaurant->user)
            ->postJson(route('api.offers.store'), $this->payload(['dish_ids' => [$dish->id], 'deal' => $this->deal()]), ['Accept-Language' => 'ar'])
            ->assertJsonValidationErrors(['deal' => 'العرض الذي له طبق خاص به لا يكون فيه خصم ولا أطباق أخرى.']);

        $this->assertSame(0, Offer::query()->count());
        $this->assertSame(1, Dish::query()->count());
    }

    public function test_a_deal_is_checked_as_a_dish_is(): void
    {
        $theirs = Category::factory()->create();

        $this->store(['deal' => ['name' => ['en' => ''], 'price' => 0, 'category_id' => $theirs->id, 'currency' => 'EUR']])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'deal.name.en' => "A dish name is required in your menu's main language (English).",
                'deal.price' => 'Give the deal dish a price above 0.',
                'deal.category_id' => 'Choose one of your categories.',
                'deal.currency',
            ]);

        $this->store(['deal' => ['name' => ['en' => str_repeat('a', 61)]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['deal.name.en', 'deal.price' => 'Give the deal dish a price.', 'deal.category_id' => 'Choose the category the deal dish shows in.']);

        $this->assertSame(0, Dish::query()->count());
    }

    public function test_a_deal_in_the_second_currency(): void
    {
        $this->restaurant->update(['second_currency' => 'LBP', 'exchange_rate' => '0.0000112']);

        $this->store(['deal' => $this->deal(['currency' => 'LBP', 'price' => 2000000])])
            ->assertCreated()
            ->assertJsonPath('data.deal.currency', 'LBP');

        // The main currency is kept as none, like a dish's.
        $this->update(Offer::query()->firstOrFail(), ['deal' => $this->deal(['currency' => 'USD'])])
            ->assertOk()
            ->assertJsonPath('data.deal.currency', null);
    }

    public function test_a_new_deal_counts_against_the_dish_limit(): void
    {
        FeatureGrant::factory()->for($this->restaurant)->forFeature(Feature::DishLimit, 1)->create(['mode' => GrantMode::Set]);
        Dish::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $this->store(['deal' => $this->deal()])
            ->assertUnprocessable()
            // Premium's dishes are fair use, so it says so.
            ->assertJsonValidationErrors(['deal.name' => 'You have reached the fair-use limit of 1 dishes. Contact us if you need more.']);

        // Nothing half saved.
        $this->assertSame(0, Offer::query()->count());
        $this->assertSame(1, Dish::query()->count());

        // An offer without one is no new dish.
        $this->store(['action' => 'none'])->assertCreated();
    }

    public function test_editing_a_deal_it_has_is_no_new_dish(): void
    {
        $this->store(['deal' => $this->deal()])->assertCreated();
        FeatureGrant::factory()->for($this->restaurant)->forFeature(Feature::DishLimit, 1)->create(['mode' => GrantMode::Set]);

        $this->update(Offer::query()->firstOrFail(), ['deal' => $this->deal(['price' => 27])])
            ->assertOk()
            ->assertJsonPath('data.deal.price', '27.00');
    }

    public function test_deleting_the_offer_deletes_its_deal_and_its_photo(): void
    {
        $this->store(['image_key' => $this->upload(), 'deal' => $this->deal()])->assertCreated();
        $offer = Offer::query()->firstOrFail();
        $deal = $offer->deal;
        $photo = $deal->getFirstMedia('image');

        $this->actingAs($this->restaurant->user)->deleteJson(route('api.offers.destroy', $offer))->assertNoContent();

        $this->assertModelMissing($deal);
        Storage::disk($photo->disk)->assertMissing($photo->getPathRelativeToRoot());
    }

    public function test_another_offers_deal_is_not_a_dish_to_choose(): void
    {
        $offer = Offer::factory()->create(['restaurant_id' => $this->restaurant->id]);
        $deal = Dish::factory()->dealOf($offer)->create(['price' => '25.00']);

        $this->store(['discount_type' => 'percent', 'discount_value' => 10, 'dish_ids' => [$deal->id]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dish_ids.0']);
    }

    public function test_the_dish_list_names_the_offer_and_where_it_stands(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 10:00', 'Asia/Beirut'));
        $offers = [
            'live' => Offer::factory()->create(['restaurant_id' => $this->restaurant->id, 'title' => ['en' => 'Lunch deal']]),
            'scheduled' => Offer::factory()->create(['restaurant_id' => $this->restaurant->id, 'starts_at' => Carbon::parse('2026-11-13 18:00', 'Asia/Beirut')->utc()]),
            'ended' => Offer::factory()->create(['restaurant_id' => $this->restaurant->id, 'ends_at' => Carbon::parse('2026-11-01 00:00', 'Asia/Beirut')->utc()]),
            'paused' => Offer::factory()->create(['restaurant_id' => $this->restaurant->id, 'is_active' => false]),
        ];
        $deals = collect($offers)->map(fn (Offer $offer): Dish => Dish::factory()->dealOf($offer)->create());
        $plain = Dish::factory()->create(['restaurant_id' => $this->restaurant->id]);

        $data = collect($this->actingAs($this->restaurant->user)->getJson(route('api.dishes.index'))->assertOk()->json('data'))->keyBy('id');

        $this->assertNull($data[$plain->id]['offer']);
        $this->assertSame(['id' => $offers['live']->id, 'title' => ['en' => 'Lunch deal', 'ar' => null], 'status' => 'live', 'next_start' => null], $data[$deals['live']->id]['offer']);
        $this->assertSame('scheduled', $data[$deals['scheduled']->id]['offer']['status']);
        $this->assertSame('2026-11-13T18:00', $data[$deals['scheduled']->id]['offer']['next_start']);
        $this->assertSame('ended', $data[$deals['ended']->id]['offer']['status']);
        $this->assertSame('paused', $data[$deals['paused']->id]['offer']['status']);
        Carbon::setTestNow();
    }

    public function test_the_dish_list_says_off_when_the_package_keeps_the_offer_off_the_menu(): void
    {
        $offer = Offer::factory()->create(['restaurant_id' => $this->restaurant->id]);
        $deal = Dish::factory()->dealOf($offer)->create();
        $status = fn (): string => $this->actingAs($this->restaurant->user)->getJson(route('api.dishes.index'))
            ->assertOk()->json('data.0.offer.status');

        $this->assertSame('live', $status());

        // An admin turns offers off for this restaurant: the menu leaves the deal out.
        FeatureGrant::factory()->for($this->restaurant)->forFeature(Feature::Offers, 0)->create(['mode' => GrantMode::Set]);
        $this->assertSame('off', $status());
        $this->assertSame($deal->id, $this->actingAs($this->restaurant->user)->getJson(route('api.dishes.index'))->json('data.0.id'));
    }

    public function test_a_deal_dish_is_edited_and_deleted_as_a_dish_and_keeps_its_offer(): void
    {
        $this->store(['deal' => $this->deal()])->assertCreated();
        $offer = Offer::query()->firstOrFail();
        $deal = $offer->deal;
        $other = Offer::factory()->create(['restaurant_id' => $this->restaurant->id]);

        // `offer_id` is never written through the dishes API.
        $this->actingAs($this->restaurant->user)
            ->patchJson(route('api.dishes.update', $deal), ['name' => ['en' => 'Feast'], 'price' => '29', 'is_available' => false, 'offer_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Feast')
            ->assertJsonPath('data.offer.id', $offer->id);
        $this->assertSame($offer->id, $deal->fresh()->offer_id);

        $this->actingAs($this->restaurant->user)
            ->postJson(route('api.dishes.store'), ['name' => ['en' => 'Soup'], 'price' => '4', 'category_id' => $this->category->id, 'offer_id' => $offer->id])
            ->assertCreated()
            ->assertJsonPath('data.offer', null);

        // Deleted as a dish: the offer is left without a deal or a dish.
        $this->actingAs($this->restaurant->user)->deleteJson(route('api.dishes.destroy', $deal))->assertNoContent();
        $this->assertNull($offer->fresh()->deal);
        $this->assertSame([], $offer->fresh()->dishes()->pluck('dishes.id')->all());
        $this->actingAs($this->restaurant->user)->getJson(route('api.offers.index'))->assertJsonPath('data.0.deal', null);
    }
}
