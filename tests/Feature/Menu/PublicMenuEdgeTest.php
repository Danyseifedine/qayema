<?php

namespace Tests\Feature\Menu;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class PublicMenuEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_hostile_content_is_escaped_on_the_public_page(): void
    {
        $restaurant = $this->published(['name' => ['en' => '<script>alert("x")</script> Diner'], 'default_locale' => 'en']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => '<img src=x onerror=alert(1)>']]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id,
            'name' => ['en' => '"><svg onload=alert(2)>'], 'ingredients' => ['en' => 'javascript:alert(3)']]);

        $html = $this->get(route('public.menu', $restaurant->slug))->assertOk()->getContent();

        $this->assertStringNotContainsString('<script>alert', $html);
        $this->assertStringNotContainsString('<img src=x', $html);
        $this->assertStringNotContainsString('<svg onload', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_a_dish_without_a_price_shows_no_price(): void
    {
        $restaurant = $this->published(['default_locale' => 'en']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Specials']]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => ['en' => 'Market Fish'], 'price' => null]);

        $html = $this->get(route('public.menu', $restaurant->slug))->assertOk()->getContent();

        $this->assertStringContainsString('Market Fish', $html);
        $this->assertStringNotContainsString('class="price"', $html);
    }

    public function test_an_unknown_currency_code_falls_back_to_the_code_itself(): void
    {
        $restaurant = $this->published(['default_locale' => 'en', 'currency' => 'XXX']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'A']]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => ['en' => 'B'], 'price' => 5]);

        $this->get(route('public.menu', $restaurant->slug))->assertOk()->assertSee('XXX5.00');
    }

    public function test_a_category_with_only_unavailable_dishes_is_hidden_entirely(): void
    {
        $restaurant = $this->published(['default_locale' => 'en']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Seasonal Only']]);
        Dish::factory()->unavailable()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);

        $this->get(route('public.menu', $restaurant->slug))->assertOk()->assertDontSee('Seasonal Only');
    }

    public function test_an_uncategorised_dish_does_not_appear(): void
    {
        $restaurant = $this->published(['default_locale' => 'en']);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => null, 'name' => ['en' => 'Orphan Dish']]);

        $this->get(route('public.menu', $restaurant->slug))->assertOk()->assertDontSee('Orphan Dish');
    }

    public function test_a_deactivated_template_takes_the_menu_offline(): void
    {
        $restaurant = $this->published();
        Template::whereKey($restaurant->template_id)->update(['is_active' => false]);

        $this->get(route('public.menu', $restaurant->slug))->assertNotFound();
    }

    public function test_the_fallback_locale_renders_when_the_default_has_no_translation(): void
    {
        $restaurant = $this->published(['name' => ['en' => 'English Only'], 'default_locale' => 'ar']);

        // No Arabic name saved — the page must still show something, not blank.
        $this->get(route('public.menu', $restaurant->slug))->assertOk()->assertSee('English Only');
    }

    public function test_the_menu_carries_no_security_sensitive_headers_gaps(): void
    {
        $restaurant = $this->published();

        $this->get(route('public.menu', $restaurant->slug))
            ->assertOk()
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_the_slug_lookup_is_exact(): void
    {
        $this->published(['slug' => 'exact-slug']);

        $this->get('/exact-slug')->assertOk();
        $this->get('/exact-slug-2')->assertNotFound();
        $this->get('/EXACT-SLUG')->assertNotFound();
    }
}
