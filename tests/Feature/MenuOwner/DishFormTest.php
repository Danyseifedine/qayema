<?php

namespace Tests\Feature\MenuOwner;

use App\Models\Dish;
use App\Models\Menu;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DishFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_dish_edit_page_includes_full_form_and_dish_form_id(): void
    {
        $owner = User::factory()->create();
        $menu = Menu::query()->create([
            'user_id' => $owner->id,
            'name' => 'Test Menu',
            'slug' => 'test-menu-dish-form',
            'menu_style' => 'home',
            'is_active' => true,
        ]);
        $dish = Dish::query()->create([
            'menu_id' => $menu->id,
            'name' => 'Test Dish',
            'display_order' => 0,
            'is_available' => true,
        ]);

        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get(route('menu-owner.dishes.edit', $dish));

        $response->assertOk();
        $response->assertSee('id="dish-form"', false);
        $response->assertSee('id="category_id"', false);
        $response->assertSee('id="name"', false);
        $response->assertSee('id="images-container"', false);
        $response->assertSee('id="dish-existing-images-json"', false);
    }
}
