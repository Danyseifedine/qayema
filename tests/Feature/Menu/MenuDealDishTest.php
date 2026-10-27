<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Models\Category;
use App\Models\Dish;
use App\Models\FeatureGrant;
use App\Models\Offer;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * An offer's deal dish on the guest menu: drawn in its category while its
 * offer is among the menu's offers (hidden when not live as the page is
 * drawn, with its category and tab when it is all there is), never drawn
 * otherwise, and never in the search data.
 */
class MenuDealDishTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    private Category $deals;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Beirut'));
        $this->defaultPackageIncludes(Feature::Offers, Feature::OfferSchedules, Feature::OfferDiscounts);
        $this->restaurant = $this->published(['slug' => 'olive', 'timezone' => 'Asia/Beirut', 'currency' => 'USD']);
        $this->deals = Category::factory()->create(['restaurant_id' => $this->restaurant->id, 'name' => ['en' => 'Deals'], 'display_order' => 2]);
        Dish::factory()->create([
            'restaurant_id' => $this->restaurant->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $this->restaurant->id, 'display_order' => 1])->id,
            'name' => ['en' => 'Kafta'],
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    /** An offer with its deal dish ("Family meal") in the Deals category. */
    private function deal(array $offer = []): Dish
    {
        $made = Offer::factory()->create(['restaurant_id' => $this->restaurant->id, 'action' => 'dishes', ...$offer]);

        return Dish::factory()->dealOf($made)->create([
            'category_id' => $this->deals->id,
            'name' => ['en' => 'Family meal'],
            'price' => '25.00',
        ]);
    }

    private function menu(string $query = ''): TestResponse
    {
        return $this->get(route('public.menu', $this->restaurant->slug).$query)->assertOk();
    }

    /** The opening tag of the first element matching, or null. */
    private function tag(TestResponse $response, string $pattern): ?string
    {
        return preg_match($pattern, (string) $response->getContent(), $match) === 1 ? $match[0] : null;
    }

    private function dishTag(TestResponse $response, Dish $dish): ?string
    {
        return $this->tag($response, '/<article[^>]*data-dish="'.$dish->id.'"[^>]*>/s');
    }

    private function sectionTag(TestResponse $response): ?string
    {
        return $this->tag($response, '/<section class="category" id="category-'.$this->deals->id.'"[^>]*>/');
    }

    private function tabTag(TestResponse $response): ?string
    {
        return $this->tag($response, '/<button[^>]*data-tab="category-'.$this->deals->id.'"[^>]*>/');
    }

    public function test_a_deal_of_a_live_offer_shows_in_its_category(): void
    {
        $dish = $this->deal();

        $response = $this->menu();
        $tag = $this->dishTag($response, $dish);

        $this->assertNotNull($tag);
        $this->assertStringContainsString('data-deal-offer="'.$dish->offer_id.'"', $tag);
        $this->assertStringNotContainsString('hidden', $tag);
        $this->assertStringNotContainsString('hidden', (string) $this->sectionTag($response));
        $this->assertStringNotContainsString('hidden', (string) $this->tabTag($response));
        $response->assertSee('data-price="25.00"', false);
    }

    public function test_a_deal_of_an_offer_not_live_yet_is_drawn_hidden_with_a_category_of_nothing_else(): void
    {
        // Evenings only: at noon the phone shows it later (menu-offers.js).
        $dish = $this->deal(['time_windows' => [['from' => '18:00', 'to' => '22:00']]]);

        $response = $this->menu();

        $this->assertStringContainsString('hidden data-deal-off', (string) $this->dishTag($response, $dish));
        $this->assertStringContainsString('hidden data-deal-off', (string) $this->sectionTag($response));
        $this->assertStringContainsString('hidden data-deal-off', (string) $this->tabTag($response));
        // The phone has its offer to judge.
        $response->assertSee('js/menu-offers.js', false);
    }

    public function test_a_category_with_more_than_hidden_deals_stays(): void
    {
        $dish = $this->deal(['starts_at' => CarbonImmutable::parse('2026-11-12 00:00', 'Asia/Beirut')->utc()]);
        Dish::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $this->deals->id, 'name' => ['en' => 'Lemonade']]);

        $response = $this->menu();

        $this->assertStringContainsString('hidden data-deal-off', (string) $this->dishTag($response, $dish));
        $this->assertStringNotContainsString('hidden', (string) $this->sectionTag($response));
        $this->assertStringNotContainsString('hidden', (string) $this->tabTag($response));
    }

    public function test_a_deal_of_an_offer_the_menu_does_not_carry_is_not_drawn(): void
    {
        $paused = $this->deal(['is_active' => false]);
        $ended = $this->deal(['ends_at' => CarbonImmutable::parse('2026-11-01 00:00', 'Asia/Beirut')->utc()]);

        $response = $this->menu();

        $this->assertNull($this->dishTag($response, $paused));
        $this->assertNull($this->dishTag($response, $ended));
        $response->assertDontSee('Family meal');
        // Nothing else in the category: no heading, no tab.
        $this->assertNull($this->sectionTag($response));
        $this->assertNull($this->tabTag($response));
    }

    public function test_without_offers_or_schedules_on_the_package_the_deal_is_not_drawn(): void
    {
        $dish = $this->deal(['time_windows' => [['from' => '10:00', 'to' => '22:00']]]);

        // A schedule the package no longer runs keeps the offer off the menu.
        FeatureGrant::factory()->for($this->restaurant)->forFeature(Feature::OfferSchedules, 0)->create(['mode' => GrantMode::Set]);
        $this->assertNull($this->dishTag($this->menu(), $dish));

        FeatureGrant::factory()->for($this->restaurant)->forFeature(Feature::Offers, 0)->create(['mode' => GrantMode::Set]);
        $response = $this->menu();
        $this->assertNull($this->dishTag($response, $dish));
        $response->assertDontSee('Family meal');
    }

    public function test_the_owner_previewing_a_paused_offer_sees_its_deal(): void
    {
        $dish = $this->deal(['is_active' => false]);
        $this->actingAs($this->restaurant->user);

        $tag = $this->dishTag($this->menu('?offer='.$dish->offer_id), $dish);

        $this->assertNotNull($tag);
        $this->assertStringNotContainsString('hidden', $tag);
    }

    public function test_search_engines_never_read_a_deal(): void
    {
        $this->deal();

        $response = $this->menu();
        preg_match('/<script type="application\/ld\+json">(.*?)<\/script>/s', (string) $response->getContent(), $match);
        $schema = json_decode($match[1], true, flags: JSON_THROW_ON_ERROR);

        $items = collect($schema['hasMenu']['hasMenuSection'])->flatMap(fn (array $section): array => $section['hasMenuItem'])->pluck('name')->all();
        $this->assertSame(['Kafta'], $items);
        // The deal's category held nothing else, so it is no section either.
        $this->assertCount(1, $schema['hasMenu']['hasMenuSection']);
    }
}
