<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Menu;
use App\Models\User;
use App\Services\UserCascadeDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class UserCascadeDeletionServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deletes_user_and_owned_menu_data(): void
    {
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);
        $menu = Menu::query()->create([
            'user_id' => $owner->id,
            'name' => 'M',
            'slug' => 'm-slug-delete-test',
            'menu_style' => 'home',
            'is_active' => true,
        ]);
        $category = Category::query()->create([
            'menu_id' => $menu->id,
            'name' => 'C',
            'display_order' => 0,
        ]);
        $dish = Dish::query()->create([
            'menu_id' => $menu->id,
            'category_id' => $category->id,
            'name' => 'D',
            'display_order' => 0,
            'is_available' => true,
        ]);

        $this->actingAs(User::factory()->admin()->create());

        app(UserCascadeDeletionService::class)->delete($owner->fresh());

        $this->assertDatabaseMissing('users', ['id' => $owner->id]);
        $this->assertDatabaseMissing('menus', ['id' => $menu->id]);
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
        $this->assertDatabaseMissing('dishes', ['id' => $dish->id]);
    }

    public function test_throws_when_deleting_last_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin);

        $this->expectException(ValidationException::class);
        app(UserCascadeDeletionService::class)->delete($admin);
    }

    public function test_allows_deleting_admin_when_another_admin_exists(): void
    {
        $adminA = User::factory()->admin()->create(['email' => 'a@example.test']);
        $adminB = User::factory()->admin()->create(['email' => 'b@example.test']);

        $this->actingAs($adminA);

        app(UserCascadeDeletionService::class)->delete($adminB->fresh());

        $this->assertDatabaseMissing('users', ['id' => $adminB->id]);
        $this->assertDatabaseHas('users', ['id' => $adminA->id]);
    }

    public function test_throws_when_user_deletes_own_account_via_service(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'self@example.test']);
        User::factory()->admin()->create(['email' => 'other@example.test']);

        $this->actingAs($admin);

        $this->expectException(ValidationException::class);
        app(UserCascadeDeletionService::class)->delete($admin->fresh());
    }
}
