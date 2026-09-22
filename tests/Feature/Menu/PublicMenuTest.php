<?php

namespace Tests\Feature\Menu;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicMenuTest extends TestCase
{
    use RefreshDatabase;

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
            'template_settings' => $template->defaultSettings(),
        ], $attributes));
    }

    public function test_the_menu_renders_for_a_published_restaurant(): void
    {
        $restaurant = $this->published();

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertSee("Joe's Diner");
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
        $restaurant->update(['template_settings' => ['primary_color' => '#112233']]);

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
