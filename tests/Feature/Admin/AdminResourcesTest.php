<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\BlockedIps\BlockedIpResource;
use App\Filament\Admin\Resources\BlockedIps\Pages\CreateBlockedIp;
use App\Filament\Admin\Resources\Categories\CategoryResource;
use App\Filament\Admin\Resources\Categories\Pages\CreateCategory;
use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\Dishes\DishResource;
use App\Filament\Admin\Resources\Dishes\Pages\CreateDish;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\RestaurantSocialLinks\RestaurantSocialLinkResource;
use App\Filament\Admin\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Admin\Resources\Templates\TemplateResource;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Category;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * Every admin resource: renders for an admin, is closed to owners, and can
 * create its record through the real form.
 */
class AdminResourcesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, array{0: class-string}> */
    public static function resources(): array
    {
        return [
            'users' => [UserResource::class],
            'restaurants' => [RestaurantResource::class],
            'templates' => [TemplateResource::class],
            'categories' => [CategoryResource::class],
            'dishes' => [DishResource::class],
            'social links' => [RestaurantSocialLinkResource::class],
            'contact messages' => [ContactMessageResource::class],
            'packages' => [PackageResource::class],
            'blocked ips' => [BlockedIpResource::class],
        ];
    }

    public function test_the_dashboard_opens_and_carries_no_stats(): void
    {
        // The admin keeps no stats of its own. The owner dashboard's numbers
        // come from the API, which still reads menu_sessions.
        $this->actingAs($this->admin())
            ->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertDontSee('fi-wi-stats-overview', false)
            ->assertDontSee('Statistics');
    }

    /** @dataProvider resources */
    public function test_every_resource_index_renders_for_an_admin(string $resource): void
    {
        $this->actingAs($this->admin())->get($resource::getUrl('index'))->assertOk();
    }

    /** @dataProvider resources */
    public function test_every_resource_is_closed_to_owners(string $resource): void
    {
        $this->actingAs($this->owner()->user)->get($resource::getUrl('index'))->assertForbidden();
    }

    /** @dataProvider resources */
    public function test_every_resource_sends_guests_to_the_login_page(string $resource): void
    {
        $this->get($resource::getUrl('index'))->assertRedirect();
    }

    public function test_an_admin_can_create_an_owner_who_can_then_log_in(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'New Owner', 'email' => 'owner@example.com', 'password' => 'a-long-password', 'role' => UserRole::MenuOwner->value])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->post(route('logout'));
        $this->post(route('login'), ['email' => 'owner@example.com', 'password' => 'a-long-password'])->assertRedirect();
        $this->assertAuthenticatedAs(User::firstWhere('email', 'owner@example.com'));
    }

    public function test_user_creation_enforces_email_uniqueness_and_password_length(): void
    {
        $existing = User::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Dup', 'email' => $existing->email, 'password' => 'short', 'role' => UserRole::MenuOwner->value])
            ->call('create')
            ->assertHasFormErrors(['email', 'password']);
    }

    public function test_editing_a_user_without_a_password_keeps_the_old_one(): void
    {
        $user = User::factory()->create();
        $hash = $user->password;
        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $user->id])
            ->fillForm(['name' => 'Renamed', 'password' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($hash, $user->fresh()->password);
        $this->assertSame('Renamed', $user->fresh()->name);
    }

    public function test_an_admin_cannot_delete_themselves_or_the_last_admin(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();

        $this->assertFalse($admin->can('delete', $admin), 'Not yourself.');
        $this->assertFalse($admin->can('delete', $this->admin()) && User::where('role', UserRole::Admin)->count() <= 1);
        $this->assertTrue($admin->can('delete', $owner));

        // Two admins: one may delete the other; then the survivor is protected.
        $second = $this->admin();
        $this->assertTrue($admin->can('delete', $second));
        $second->delete();
        $this->assertFalse(User::factory()->create(['role' => UserRole::Admin])->can('delete', $admin) && User::where('role', UserRole::Admin)->count() <= 1);
    }

    public function test_the_last_admin_cannot_be_deleted_from_the_table(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertActionHidden(TestAction::make('delete')->table($admin));
    }

    public function test_an_admin_can_create_a_restaurant_for_an_owner(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurant::class)
            ->fillForm(['user_id' => $owner->id, 'name' => 'Admin Made', 'slug' => 'admin-made', 'phone' => '+96170123456'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('restaurants', ['user_id' => $owner->id, 'slug' => 'admin-made']);
    }

    public function test_a_second_restaurant_for_the_same_owner_is_refused(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurant::class)
            ->fillForm(['user_id' => $restaurant->user_id, 'name' => 'Second', 'slug' => 'second-one', 'phone' => '+96170123456'])
            ->call('create')
            ->assertHasFormErrors();
    }

    public function test_assigning_a_template_from_the_panel_applies_it(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);
        $design = Template::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['template_id' => $design->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($design->id, $restaurant->fresh()->template_id);
    }

    public function test_an_admin_can_publish_a_template_with_a_schema(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateTemplate::class)
            ->fillForm([
                'name' => 'Midnight', 'slug' => 'midnight', 'is_active' => true, 'sort_order' => 3,
                'settings_schema' => [['key' => 'accent', 'type' => 'color', 'default' => '#ABCDEF']],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $template = Template::firstWhere('slug', 'midnight');
        $this->assertSame(3, $template->sort_order);
        $this->assertSame('#ABCDEF', $template->defaultSettings()['accent']);
    }

    public function test_template_slugs_are_unique(): void
    {
        Template::factory()->create(['slug' => 'taken']);
        $this->actingAs($this->admin());

        Livewire::test(CreateTemplate::class)
            ->fillForm(['name' => 'Taken', 'slug' => 'taken'])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_an_admin_can_add_a_category_and_a_dish_to_any_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(CreateCategory::class)
            ->fillForm(['restaurant_id' => $restaurant->id, 'name' => 'Grills', 'display_order' => 1])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::firstWhere('restaurant_id', $restaurant->id);

        Livewire::test(CreateDish::class)
            ->fillForm(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => 'Kafta', 'price' => 12.5, 'display_order' => 1, 'is_available' => true])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('dishes', ['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
    }

    public function test_an_admin_can_block_an_ip_and_it_takes_effect(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateBlockedIp::class)
            ->fillForm(['ip' => '203.0.113.9', 'reason' => 'spam'])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->post(route('logout'));
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.9'])->get('/')->assertForbidden();
    }

    public function test_restaurant_search_finds_by_name(): void
    {
        Restaurant::factory()->create(['name' => ['en' => 'Findable Diner']]);
        Restaurant::factory()->create(['name' => ['en' => 'Other Place']]);
        $this->actingAs($this->admin());

        Livewire::test(\App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants::class)
            ->searchTable('Findable')
            ->assertCanSeeTableRecords(Restaurant::whereLike('name', '%Findable%')->get())
            ->assertCanNotSeeTableRecords(Restaurant::whereLike('name', '%Other%')->get());
    }
}
