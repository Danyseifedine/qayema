<?php

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Support\CategoryIcons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A category's icon: one of the picker's (App\Support\CategoryIcons) or none,
 * saved from the dashboard and drawn in the menu's tabs and headings.
 */
class CategoryIconTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_a_category_is_made_and_changed_with_an_icon(): void
    {
        $shop = $this->owner();

        $made = $this->actingAs($shop->user)
            ->postJson(route('api.categories.store'), ['name' => ['en' => 'Pizza'], 'icon' => 'pizza'])
            ->assertCreated()
            ->assertJsonPath('data.icon', 'pizza');
        $category = Category::query()->findOrFail($made->json('data.id'));

        $this->actingAs($shop->user)
            ->patchJson(route('api.categories.update', $category), ['name' => ['en' => 'Pizza'], 'icon' => 'flame'])
            ->assertOk()
            ->assertJsonPath('data.icon', 'flame');

        // A client that does not send it keeps the icon; null takes it away.
        $this->actingAs($shop->user)
            ->patchJson(route('api.categories.update', $category), ['name' => ['en' => 'Pizzas']])
            ->assertOk()
            ->assertJsonPath('data.icon', 'flame');
        $this->actingAs($shop->user)
            ->patchJson(route('api.categories.update', $category), ['name' => ['en' => 'Pizzas'], 'icon' => null])
            ->assertOk()
            ->assertJsonPath('data.icon', null);

        $this->actingAs($shop->user)->getJson(route('api.categories.index'))->assertJsonPath('data.0.icon', null);
    }

    public function test_only_the_pickers_icons(): void
    {
        $shop = $this->owner();

        foreach (['spaceship', ['pizza'], str_repeat('a', 40), 12] as $icon) {
            $this->actingAs($shop->user)
                ->postJson(route('api.categories.store'), ['name' => ['en' => 'Pizza'], 'icon' => $icon])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['icon']);
        }
        $this->assertSame(0, Category::query()->count());
    }

    public function test_every_icon_is_a_drawing_and_an_unknown_one_none(): void
    {
        $this->assertCount(30, CategoryIcons::keys());

        foreach (CategoryIcons::keys() as $key) {
            $svg = (string) CategoryIcons::svg($key);
            $this->assertStringStartsWith('<svg viewBox="0 0 24 24"', $svg, $key);
            $this->assertStringContainsString('<path d="', $svg, $key);
            $this->assertStringContainsString('aria-hidden="true"', $svg, $key);
        }

        $this->assertNull(CategoryIcons::svg(null));
        $this->assertNull(CategoryIcons::svg('spaceship'));
    }

    public function test_the_menu_draws_the_icon_in_the_tab_only(): void
    {
        $shop = $this->published(['slug' => 'beit']);
        $pizza = Category::factory()->create(['restaurant_id' => $shop->id, 'name' => ['en' => 'Pizza'], 'icon' => 'pizza']);
        $plain = Category::factory()->create(['restaurant_id' => $shop->id, 'name' => ['en' => 'Sides'], 'icon' => null]);
        foreach ([$pizza, $plain] as $category) {
            Dish::factory()->create(['restaurant_id' => $shop->id, 'category_id' => $category->id, 'is_available' => true]);
        }

        $html = (string) $this->get(route('public.menu', 'beit'))->assertOk()->getContent();
        $drawing = (string) CategoryIcons::svg('pizza');

        $this->assertSame(1, substr_count($html, '<span class="tab-icon">'.$drawing.'</span>'));
        // A category without one has none, and no heading draws one.
        $this->assertSame(1, substr_count($html, 'class="tab-icon"'));
        $this->assertSame(1, substr_count($html, $drawing));
        $this->assertStringContainsString('<h2><bdi>Pizza</bdi></h2>', $html);
    }

    public function test_a_restaurant_can_hold_categories_with_and_without_icons(): void
    {
        $shop = Restaurant::factory()->create();
        Category::factory()->create(['restaurant_id' => $shop->id, 'icon' => 'coffee']);

        $this->assertSame(['coffee'], $shop->categories()->pluck('icon')->all());
    }
}
