<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuEvent;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class MenuEventsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    private function published(array $attributes = []): Restaurant
    {
        $template = Template::factory()->create(['slug' => 'classic']);

        return Restaurant::factory()->create([
            'slug' => 'tracked',
            'is_active' => true,
            'template_id' => $template->id,
            ...$attributes,
        ]);
    }

    private function send(Restaurant $restaurant, array $events)
    {
        return $this->postJson(route('public.events', $restaurant->slug), ['events' => $events]);
    }

    public function test_it_stores_what_guests_did(): void
    {
        $restaurant = $this->published();
        $dish = Dish::factory()->for($restaurant)->create();
        $category = Category::factory()->for($restaurant)->create();

        $this->send($restaurant, [
            ['type' => 'dish_add', 'dish_id' => $dish->id],
            ['type' => 'category_open', 'category_id' => $category->id],
            ['type' => 'whatsapp'],
            ['type' => 'social', 'value' => 'instagram'],
        ])->assertNoContent();

        $this->assertSame(
            ['dish_add', 'category_open', 'whatsapp', 'social'],
            MenuEvent::query()->orderBy('id')->pluck('type')->map->value->all(),
        );
        $this->assertDatabaseHas('menu_events', ['type' => 'dish_add', 'dish_id' => $dish->id, 'restaurant_id' => $restaurant->id]);
        $this->assertDatabaseHas('menu_events', ['type' => 'social', 'value' => 'instagram']);
    }

    public function test_another_restaurants_dish_is_dropped(): void
    {
        $restaurant = $this->published();
        $foreign = Dish::factory()->create();

        $this->send($restaurant, [
            ['type' => 'dish_add', 'dish_id' => $foreign->id],
            ['type' => 'map'],
        ])->assertNoContent();

        $this->assertSame(['map'], MenuEvent::query()->pluck('type')->map->value->all());
    }

    public function test_fields_a_type_does_not_carry_are_cleared(): void
    {
        $restaurant = $this->published();
        $dish = Dish::factory()->for($restaurant)->create();

        $this->send($restaurant, [['type' => 'call', 'dish_id' => $dish->id, 'value' => 'anything']])->assertNoContent();

        $this->assertDatabaseHas('menu_events', ['type' => 'call', 'dish_id' => null, 'value' => null]);
    }

    public function test_search_terms_are_normalised_and_too_short_ones_dropped(): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, [
            ['type' => 'search', 'value' => '  Chicken   SHAWARMA '],
            ['type' => 'search_miss', 'value' => 'a'],
        ])->assertNoContent();

        $this->assertSame(['chicken shawarma'], MenuEvent::query()->pluck('value')->all());
    }

    public function test_a_language_the_menu_does_not_offer_is_dropped(): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, [
            ['type' => 'language', 'value' => 'ar'],
            ['type' => 'language', 'value' => 'klingon'],
        ])->assertNoContent();

        $this->assertSame(['ar'], MenuEvent::query()->pluck('value')->all());
    }

    public function test_an_unknown_type_is_rejected(): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, [['type' => 'hack']])->assertStatus(422);
        $this->assertDatabaseCount('menu_events', 0);
    }

    public function test_a_batch_is_capped(): void
    {
        $restaurant = $this->published();

        $this->send($restaurant, array_fill(0, 26, ['type' => 'map']))->assertStatus(422);
    }

    public function test_a_switched_off_restaurant_takes_nothing(): void
    {
        $restaurant = $this->published(['is_active' => false]);

        $this->send($restaurant, [['type' => 'map']])->assertNotFound();
        $this->assertDatabaseCount('menu_events', 0);
    }

    public function test_the_menu_loads_the_tracker_and_tags_its_links(): void
    {
        $restaurant = $this->published(['google_maps_url' => 'https://maps.google.com/?q=1', 'phone' => '+96170000000']);
        $restaurant->socialLinks()->create(['platform' => 'instagram', 'url' => 'https://instagram.com/tracked']);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('js/menu-track.js', false)
            // @js escapes the slashes.
            ->assertSee('\/tracked\/events', false)
            ->assertSee('data-track="map"', false)
            ->assertSee('data-track="call"', false)
            ->assertSee('data-track="social" data-track-value="instagram"', false)
            ->assertSee('data-track="language"', false);
    }

    public function test_an_owners_preview_sends_nothing(): void
    {
        $restaurant = $this->published();

        $this->actingAs($restaurant->user)
            ->get(route('public.menu', ['restaurant' => $restaurant->slug, 'preview' => $restaurant->template_id]))
            ->assertOk()
            ->assertDontSee('js/menu-track.js', false);
    }

    public function test_the_visit_remembers_the_language_it_opened_in(): void
    {
        $restaurant = $this->published(['default_locale' => 'en']);

        $this->get(route('public.menu', ['restaurant' => $restaurant->slug, 'lang' => 'ar']))->assertOk();

        $this->assertDatabaseHas('menu_sessions', ['restaurant_id' => $restaurant->id, 'locale' => 'ar']);
    }

    public function test_the_retention_prune_clears_old_events_too(): void
    {
        $restaurant = $this->published();
        $restaurant->menuEvents()->create(['session_id' => 's', 'type' => 'map', 'occurred_at' => now()->subMonths(7)]);
        $restaurant->menuEvents()->create(['session_id' => 's', 'type' => 'map', 'occurred_at' => now()]);

        $this->artisan('stats:rollup')->assertSuccessful();

        $this->assertDatabaseCount('menu_events', 1);
    }
}
