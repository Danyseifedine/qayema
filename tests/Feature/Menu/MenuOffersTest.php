<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Enums\OfferPlacement;
use App\Enums\OfferRepeat;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Offer;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Offers on the guest menu: each placement drawn, live ones shown and the
 * rest hidden for the phone to show in time (menu-offers.js), discounted
 * prices on the dishes, and nothing at all without the package.
 */
class MenuOffersTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    private Dish $kafta;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Offers, Feature::OfferSchedules, Feature::OfferDiscounts);
        $this->restaurant = $this->published(['slug' => 'olive', 'timezone' => 'Asia/Beirut', 'currency' => 'USD']);
        $category = Category::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => ['en' => 'Grills']]);
        $this->kafta = Dish::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Kafta'],
            'price' => '12.50',
        ]);
    }

    private function offer(array $state = [], OfferPlacement ...$placements): Offer
    {
        return Offer::factory()->in(...($placements ?: [OfferPlacement::Banner]))->create([
            'restaurant_id' => $this->restaurant->id,
            'title' => ['en' => 'Happy hour'],
            ...$state,
        ]);
    }

    private function menu(string $query = ''): \Illuminate\Testing\TestResponse
    {
        return $this->get(route('public.menu', $this->restaurant->slug).$query)->assertOk();
    }

    public function test_a_live_banner_shows_with_its_script(): void
    {
        $offer = $this->offer();

        $this->menu()
            ->assertSee('class="offers-banner"', false)
            ->assertSee('<div class="offers-track" data-offers-track>', false)
            ->assertSee('<article class="offer offer-banner offer-shape-rectangle" data-offer="'.$offer->id.'"', false)
            ->assertSee('Happy hour')
            ->assertSee('window.QAYEMA_OFFERS', false)
            ->assertSee('js/menu-offers.js', false)
            ->assertSee("seenKey: 'qayema-offer-seen-olive'", false);
    }

    public function test_a_banner_with_a_picture_wears_it_and_one_without_wears_the_menu_colour(): void
    {
        $pictured = $this->offer(['title' => ['en' => 'With picture']]);
        $pictured->addMedia(UploadedFile::fake()->image('offer.jpg', 1200, 800))->toMediaCollection('image');
        $plain = $this->offer(['title' => ['en' => 'Plain']]);

        $this->menu()
            ->assertSee('<article class="offer offer-banner offer-shape-rectangle offer-has-photo" data-offer="'.$pictured->id.'"', false)
            ->assertSee('<article class="offer offer-banner offer-shape-rectangle" data-offer="'.$plain->id.'"', false);
    }

    public function test_a_picture_alone_is_the_whole_card_and_the_tap(): void
    {
        $offer = $this->offer(['show_text' => false, 'title' => ['en' => 'Lunch deal'], 'action' => 'dishes', 'discount_type' => 'percent', 'discount_value' => 10], OfferPlacement::Banner, OfferPlacement::Popup);
        $offer->dishes()->attach($this->kafta);
        $offer->addMedia(UploadedFile::fake()->image('promo.jpg', 1200, 800))->toMediaCollection('image');

        $html = $this->menu()->getContent();

        $this->assertStringContainsString('<article class="offer offer-banner offer-shape-rectangle offer-has-photo offer-picture-only" data-offer="'.$offer->id.'"', $html);
        $this->assertStringContainsString('<button type="button" class="offer-cover" data-offer-go aria-label="Lunch deal"></button>', $html);
        $this->assertStringContainsString('class="offer-photo"', $html);
        // No words drawn: no title heading, no "See the dishes" button.
        $this->assertStringNotContainsString('<h3 class="offer-title">Lunch deal</h3>', $html);
        $this->assertStringNotContainsString('See the dishes', $html);
    }

    public function test_each_place_takes_its_shape(): void
    {
        $banner = $this->offer(['banner_shape' => 'square']);
        $popup = $this->offer(['popup_shape' => 'tall'], OfferPlacement::Popup);
        $ad = $this->offer([], OfferPlacement::Ad);

        $this->menu()
            ->assertSee('<article class="offer offer-banner offer-shape-square" data-offer="'.$banner->id.'"', false)
            ->assertSee('<article class="offer offer-popup offer-shape-tall" data-offer="'.$popup->id.'"', false)
            ->assertSee('<article class="offer offer-ad offer-shape-rectangle" data-offer="'.$ad->id.'"', false);
    }

    public function test_the_picture_comes_sharp_for_the_screen_over_its_blurred_copy(): void
    {
        $offer = $this->offer();
        $offer->addMedia(UploadedFile::fake()->image('offer.jpg', 1800, 900))->withCustomProperties(['width' => 1800, 'height' => 900])->toMediaCollection('image');

        $html = $this->menu()->getContent();

        $this->assertStringContainsString('class="offer-photo-blur"', $html);
        $this->assertMatchesRegularExpression('/srcset="[^"]*wide[^"]* 960w, [^"]*large[^"]* 1800w" sizes="\(min-width: 1024px\) 680px, calc\(100vw - 32px\)"/', $html);
        // The first banner is seen at once: not left to load late.
        $this->assertStringContainsString('loading="eager"', $html);
        // 2:1 in a 2:1 banner: it fills the card.
        $this->assertStringNotContainsString('offer-fit-whole', $html);
    }

    public function test_a_picture_far_from_its_shape_shows_whole(): void
    {
        $tall = $this->offer(['title' => ['en' => 'Tall']]);
        $tall->addMedia(UploadedFile::fake()->image('tall.jpg', 600, 1200))->withCustomProperties(['width' => 600, 'height' => 1200])->toMediaCollection('image');
        // 16:9 in a 2:1 banner, words over it: a little cropping is fine.
        $wide = $this->offer(['title' => ['en' => 'Wide']]);
        $wide->addMedia(UploadedFile::fake()->image('wide.jpg', 1600, 900))->withCustomProperties(['width' => 1600, 'height' => 900])->toMediaCollection('image');
        // The same 16:9 as a picture alone keeps its words whole.
        $alone = $this->offer(['title' => ['en' => 'Alone'], 'show_text' => false]);
        $alone->addMedia(UploadedFile::fake()->image('alone.jpg', 1600, 900))->withCustomProperties(['width' => 1600, 'height' => 900])->toMediaCollection('image');
        // From before sizes were kept: the phone judges it.
        $older = $this->offer(['title' => ['en' => 'Older']]);
        $older->addMedia(UploadedFile::fake()->image('older.jpg', 800, 800))->toMediaCollection('image');

        $this->menu()
            ->assertSee('<article class="offer offer-banner offer-shape-rectangle offer-has-photo offer-fit-whole" data-offer="'.$tall->id.'"', false)
            ->assertSee('<article class="offer offer-banner offer-shape-rectangle offer-has-photo" data-offer="'.$wide->id.'"', false)
            ->assertSee('<article class="offer offer-banner offer-shape-rectangle offer-has-photo offer-picture-only offer-fit-whole" data-offer="'.$alone->id.'"', false)
            ->assertSee('data-fit="auto"', false);
    }

    public function test_the_carousel_has_room_for_dots_only_with_more_than_one_banner(): void
    {
        $this->offer();
        $this->assertStringNotContainsString('data-offers-dots', $this->menu()->getContent());

        $this->offer(['title' => ['en' => 'Second']]);
        $this->menu()->assertSee('<div class="offers-dots" data-offers-dots></div>', false);
    }

    public function test_an_offer_not_live_yet_is_drawn_hidden_for_the_phone_to_show(): void
    {
        $offer = $this->offer(['starts_at' => CarbonImmutable::now()->addDay()]);

        $html = $this->menu()->getContent();

        $this->assertStringContainsString('data-offer="'.$offer->id.'"  hidden', $html);
        // The whole row too, while none of its cards is live.
        $this->assertMatchesRegularExpression('/class="offers-banner"[^>]*hidden/', $html);
    }

    public function test_paused_and_ended_offers_are_left_out(): void
    {
        $this->offer(['is_active' => false, 'title' => ['en' => 'Paused deal']]);
        $this->offer(['ends_at' => CarbonImmutable::now()->subHour(), 'title' => ['en' => 'Old deal']]);

        $this->menu()
            ->assertDontSee('Paused deal')
            ->assertDontSee('Old deal')
            ->assertDontSee('window.QAYEMA_OFFERS', false);
    }

    public function test_a_pop_up_and_an_ad_wait_in_the_page(): void
    {
        $popup = $this->offer([], OfferPlacement::Popup);
        $ad = $this->offer(['ad_seconds' => 8], OfferPlacement::Ad);

        $this->menu()
            ->assertSee('<dialog class="pop offer-pop" id="offer-pop-'.$popup->id.'"', false)
            ->assertSee('id="offer-ad-'.$ad->id.'" role="status" aria-live="polite" hidden', false)
            // @js() writes the offers as JSON.parse('...'), quotes escaped.
            ->assertSee('\\u0022seconds\\u0022:8', false);
    }

    public function test_one_offer_can_be_a_pop_up_and_an_ad(): void
    {
        $offer = $this->offer(['ad_delay_seconds' => 3], OfferPlacement::Popup, OfferPlacement::Ad);

        $this->menu()
            ->assertSee('<dialog class="pop offer-pop" id="offer-pop-'.$offer->id.'"', false)
            ->assertSee('id="offer-ad-'.$offer->id.'" role="status"', false)
            ->assertSee('\\u0022ad_delay\\u0022:3', false);
    }

    public function test_no_offer_card_shows_its_discount(): void
    {
        $popup = $this->offer(['discount_type' => 'amount', 'discount_value' => 1.5], OfferPlacement::Popup);
        $banner = $this->offer(['title' => ['en' => 'Grill nights'], 'discount_type' => 'percent', 'discount_value' => 20]);
        $popup->dishes()->attach($this->kafta);
        $banner->dishes()->attach($this->kafta);

        // Neither the banner nor the pop-up: the dish shows its offer price.
        $this->menu()
            ->assertSee('Grill nights')
            ->assertDontSee('offer-badge', false)
            ->assertDontSee('-20%')
            ->assertSee('<s class="price-was">$12.50</s>', false);
    }

    public function test_a_dish_on_a_live_discount_shows_its_offer_price(): void
    {
        $offer = $this->offer(['discount_type' => 'percent', 'discount_value' => 20, 'action' => 'dishes']);
        $offer->dishes()->attach($this->kafta);

        $this->menu()
            ->assertSee('data-price="10.00"', false)
            ->assertSee('data-full-price="12.50"', false)
            ->assertSee('<span class="price">$10</span>', false)
            ->assertSee('<s class="price-was">$12.50</s>', false)
            ->assertSee('See the dishes');
    }

    public function test_outside_its_days_the_dish_keeps_its_price(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Beirut'));
        $offer = $this->offer(['discount_type' => 'amount', 'discount_value' => 2, 'repeat' => OfferRepeat::Weekly, 'weekdays' => ['sun']]);
        $offer->dishes()->attach($this->kafta);

        $this->menu()
            ->assertSee('data-price="12.50"', false)
            ->assertDontSee('class="price-was"', false)
            // The phone gets the rules to start the discount on Sunday itself.
            ->assertSee('\\u0022repeat\\u0022:\\u0022weekly\\u0022,\\u0022weekdays\\u0022:[\\u0022sun\\u0022]', false);
    }

    public function test_a_discount_that_would_make_a_dish_free_does_not_apply(): void
    {
        $offer = $this->offer(['discount_type' => 'amount', 'discount_value' => 20]);
        $offer->dishes()->attach($this->kafta);

        $this->menu()
            ->assertSee('data-price="12.50"', false)
            ->assertDontSee('class="price-was"', false);
    }

    public function test_an_offer_whose_dishes_are_all_hidden_promises_nothing(): void
    {
        $offer = $this->offer(['discount_type' => 'percent', 'discount_value' => 20, 'action' => 'dishes']);
        $offer->dishes()->attach($this->kafta);
        $this->kafta->update(['is_available' => false]);

        $this->menu()
            ->assertSee('Happy hour')
            ->assertDontSee('<bdi dir="ltr">-20%</bdi>', false)
            ->assertDontSee('See the dishes')
            // The phone is handed no dish to show either.
            ->assertSee('\\u0022dishes\\u0022:[]', false);
    }

    public function test_only_the_parts_of_offers_the_package_has_reach_the_menu(): void
    {
        $this->defaultPackageSets(Feature::OfferPopupLimit, 0);
        $this->defaultPackageSets(Feature::OfferSchedules, 0);
        $this->defaultPackageSets(Feature::OfferDiscounts, 0);
        $both = $this->offer(['title' => ['en' => 'Both places'], 'discount_type' => 'percent', 'discount_value' => 20, 'action' => 'dishes'], OfferPlacement::Banner, OfferPlacement::Popup);
        $both->dishes()->attach($this->kafta);
        $this->offer(['title' => ['en' => 'Sundays'], 'repeat' => OfferRepeat::Weekly, 'weekdays' => ['sun']]);

        $this->menu()
            // The banner shows, as a promotion: no pop-up, no discount.
            ->assertSee('Both places')
            ->assertDontSee('id="offer-pop-'.$both->id.'"', false)
            ->assertDontSee('<bdi dir="ltr">-20%</bdi>', false)
            ->assertSee('data-price="12.50"', false)
            ->assertSee('See the dishes')
            // A scheduled one stays off while schedules are not on the package.
            ->assertDontSee('Sundays');
    }

    public function test_nothing_without_the_package(): void
    {
        $this->defaultPackageSets(Feature::Offers, 0);
        $this->offer();

        $this->menu()
            ->assertDontSee('offers-banner', false)
            ->assertDontSee('menu-offers.js', false);
    }

    public function test_the_owner_previews_a_paused_offer_and_guests_cannot(): void
    {
        $offer = $this->offer(['is_active' => false, 'title' => ['en' => 'Coming soon']], OfferPlacement::Popup);

        $this->menu('?offer='.$offer->id)->assertDontSee('Coming soon');

        $this->actingAs($this->restaurant->user)
            ->get(route('public.menu', $this->restaurant->slug).'?offer='.$offer->id)
            ->assertOk()
            ->assertSee('Coming soon')
            ->assertSee('preview: '.$offer->id, false);
    }

    public function test_a_link_offer_opens_its_page_in_a_new_tab(): void
    {
        $this->offer(['action' => 'link', 'action_url' => 'https://example.com/deal']);

        $this->menu()
            ->assertSee('href="https://example.com/deal" target="_blank" rel="noopener nofollow"', false)
            ->assertSee('Learn more');
    }

    public function test_arabic_guests_read_the_offer_words_in_arabic(): void
    {
        $this->restaurant->update(['main_locale' => 'ar', 'name' => ['ar' => 'زيتون']]);
        $this->offer(['title' => ['ar' => 'ساعة سعيدة']]);

        $this->menu()
            ->assertSee('ساعة سعيدة')
            ->assertSee('العروض');
    }

    public function test_the_page_costs_the_same_queries_for_one_offer_or_many(): void
    {
        $this->offer();
        $this->menu();
        \DB::enableQueryLog();
        $this->menu();
        $one = count(\DB::getQueryLog());

        foreach (range(1, 6) as $i) {
            $offer = $this->offer(['discount_type' => 'percent', 'discount_value' => 10]);
            $offer->dishes()->attach($this->kafta);
        }
        \DB::flushQueryLog();
        $this->menu();

        $this->assertSame($one, count(\DB::getQueryLog()));
    }
}
