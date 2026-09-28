<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages, Feature::Appearance);
    }

    private function published(array $attributes = []): Restaurant
    {
        $template = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A'],
        ])->create(['slug' => 'classic']);

        return Restaurant::factory()->create(array_merge([
            'slug' => 'joes-diner',
            'name' => ['en' => "Joe's Diner"],
            'default_locale' => 'en',
            'is_active' => true,
            'template_id' => $template->id,
        ], $attributes));
    }

    public function test_the_menu_renders_for_a_published_restaurant(): void
    {
        $restaurant = $this->published();

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee("Joe's Diner");
    }

    public function test_the_menu_offers_directions_when_a_location_is_set(): void
    {
        $restaurant = $this->published(['google_maps_url' => 'https://maps.google.com/?q=33.88,35.49']);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('https://maps.google.com/?q=33.88,35.49', false)
            ->assertSee('Find us');
    }

    public function test_the_menu_shows_no_location_chip_without_a_link(): void
    {
        // There is no written address to fall back on, so nothing is shown.
        $restaurant = $this->published(['google_maps_url' => null]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertDontSee('Find us');
    }

    public function test_a_category_description_shows_under_its_heading(): void
    {
        $restaurant = $this->published(['default_locale' => 'en']);
        $category = Category::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => ['en' => 'Plates'],
            'description' => ['en' => 'From noon onwards'],
        ]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'price' => 9]);

        $html = $this->get(route('public.menu', $restaurant->slug))->assertOk()->getContent();

        $this->assertStringContainsString('<p>From noon onwards</p>', $html);
        $this->assertLessThan(strpos($html, 'From noon onwards'), strpos($html, '<h2>Plates</h2>'));
    }

    public function test_a_category_without_a_description_renders_no_empty_line(): void
    {
        $restaurant = $this->published(['default_locale' => 'en']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Plates']]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'price' => 9]);

        $html = $this->get(route('public.menu', $restaurant->slug))->assertOk()->getContent();

        $this->assertMatchesRegularExpression('#<h2>Plates</h2>\s*</div>#', $html);
    }

    public function test_the_phone_card_keeps_only_the_hours(): void
    {
        // Directions and a way to reach the restaurant live in the dock, so the
        // card under the cover no longer repeats them. The wide-screen header
        // still carries all three, since it has no dock.
        $restaurant = $this->published([
            'phone' => '70123456',
            'google_maps_url' => 'https://maps.google.com/?q=33,35',
            'opening_hours' => array_fill_keys(['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'], ['open' => '09:00', 'close' => '22:00']),
            'timezone' => 'Asia/Beirut',
        ]);

        $html = $this->get(route('public.menu', $restaurant->slug))->assertOk()->getContent();

        // The card runs from its own opening tag to the menu sections, which
        // always render — the search box only does when there are dishes.
        $start = strpos($html, '<div class="info">');
        $this->assertNotFalse($start, 'The hours card should render.');
        $card = substr($html, $start, strpos($html, '<div class="sections">') - $start);

        $this->assertStringContainsString('09:00', $card);
        $this->assertStringNotContainsString('tel:70123456', $card);
        $this->assertStringNotContainsString('maps.google.com', $card);

        // Still present in the header facts row.
        $this->assertStringContainsString('class="fact" href="tel:70123456"', $html);
    }

    public function test_the_category_filter_opens_on_all(): void
    {
        $restaurant = $this->published();

        foreach (['Starters', 'Mains'] as $name) {
            $category = Category::factory()->create([
                'restaurant_id' => $restaurant->id,
                'name' => ['en' => $name],
            ]);
            Dish::factory()->create([
                'restaurant_id' => $restaurant->id,
                'category_id' => $category->id,
                'price' => 7.5,
            ]);
        }

        $html = $this->get(route('public.menu', $restaurant->slug))->assertOk()->getContent();

        // All comes first and is the one selected, so a guest sees the whole
        // menu before choosing to narrow it.
        $this->assertStringContainsString('<button type="button" class="tab" data-tab="all" aria-current="true">', $html);
        $this->assertSame(2, substr_count($html, 'aria-current="false"'));
        $this->assertLessThan(
            strpos($html, 'Starters'),
            strpos($html, 'data-tab="all"'),
            'The All tab should be rendered before the categories.'
        );

        // The pill the script slides between tabs.
        $this->assertStringContainsString('class="tab-pill"', $html);
    }

    public function test_the_menu_shows_categories_and_available_dishes(): void
    {
        $restaurant = $this->published();
        $category = Category::factory()->create([
            'restaurant_id' => $restaurant->id,
            'name' => ['en' => 'Starters'],
        ]);
        Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Hummus'],
            'ingredients' => ['en' => 'Chickpeas, tahini'],
            'price' => 7.5,
        ]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('Starters')
            ->assertSee('Hummus')
            ->assertSee('Chickpeas, tahini')
            ->assertSee('7.50');
    }

    public function test_unavailable_dishes_are_hidden_from_guests(): void
    {
        $restaurant = $this->published();
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Mains']]);

        Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Available Dish'],
        ]);
        Dish::factory()->unavailable()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => $category->id,
            'name' => ['en' => 'Sold Out Dish'],
        ]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('Available Dish')
            ->assertDontSee('Sold Out Dish');
    }

    public function test_the_owners_colour_choice_reaches_the_page(): void
    {
        $restaurant = $this->published();
        $restaurant->update(['template_settings' => [$restaurant->template_id => ['primary_color' => '#112233']]]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('--accent: #112233', false);
    }

    public function test_an_inactive_restaurant_is_not_found(): void
    {
        $restaurant = $this->published(['is_active' => false]);

        $this->get(route('public.menu', $restaurant->slug))->assertNotFound();
    }

    public function test_a_restaurant_without_a_template_is_not_found(): void
    {
        $restaurant = $this->published(['template_id' => null]);

        $this->get(route('public.menu', $restaurant->slug))->assertNotFound();
    }

    public function test_an_unknown_slug_is_not_found(): void
    {
        $this->get('/no-such-restaurant')->assertNotFound();
    }

    public function test_a_template_without_a_view_falls_back_instead_of_erroring(): void
    {
        // A template row can exist before anyone writes its Blade file; guests
        // must still get a menu rather than a 500.
        $template = Template::factory()->create(['slug' => 'not-built-yet']);
        $restaurant = $this->published();
        $restaurant->update(['template_id' => $template->id]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee("Joe's Diner");
    }

    public function test_the_menu_does_not_swallow_reserved_paths(): void
    {
        // The catch-all slug route must never shadow the app's own pages.
        $this->get('/contact')->assertOk();
        $this->get('/privacy-policy')->assertOk();
        $this->get('/admin')->assertRedirect();
        $this->get('/up')->assertOk();
    }

    public function test_arabic_menus_render_right_to_left(): void
    {
        $restaurant = $this->published([
            'slug' => 'matam',
            'default_locale' => 'ar',
            'name' => ['ar' => 'مطعم الشام'],
        ]);

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('مطعم الشام', false);
    }
}
