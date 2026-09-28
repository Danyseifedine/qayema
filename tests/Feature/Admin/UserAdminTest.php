<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuSession;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The Users resource: the view page for every kind of account, the edit
 * page's password handling and delete, and the list's columns, filters and
 * row actions.
 */
class UserAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_view_page_shows_an_onboarded_owners_restaurant_traffic_and_content(): void
    {
        $restaurant = $this->owner(['slug' => 'cedar-grill', 'name' => ['en' => 'Cedar Grill'], 'phone' => '70123456']);
        $restaurant->user->forceFill(['onboarding_completed_at' => now()->subDays(3), 'onboarding_step' => 6])->save();

        MenuSession::factory()->for($restaurant)->create(['session_id' => 'a', 'device_type' => 'desktop', 'viewed_at' => now()]);
        MenuSession::factory()->for($restaurant)->viaQr()->create(['session_id' => 'a', 'device_type' => 'desktop', 'viewed_at' => now()->subDays(2)]);
        MenuSession::factory()->for($restaurant)->create(['session_id' => 'b', 'device_type' => 'mobile', 'viewed_at' => now()->subDays(5)]);

        $category = Category::factory()->create(['restaurant_id' => $restaurant->id]);
        Dish::factory()->count(2)->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
        Dish::factory()->unavailable()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id]);
        RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($this->admin());

        Livewire::test(ViewUser::class, ['record' => $restaurant->user->getRouteKey()])
            ->assertOk()
            ->assertSee($restaurant->user->name)
            ->assertSee($restaurant->user->email)
            ->assertSee('menu_owner')
            ->assertSee('Completed 3 days ago')
            ->assertDontSee('Not completed')
            ->assertDontSee('Wizard step')
            ->assertSee('Cedar Grill')
            ->assertSee(url('/cedar-grill'), false)
            ->assertSee('Active')
            ->assertSee('70123456')
            ->assertSee('USD')
            ->assertSee('Traffic &amp; Analytics', false)
            ->assertSeeInOrder(['Total Views', '3', 'Unique Visitors', '2', 'QR Scans', '1', 'Views Today', '1'])
            ->assertSee('desktop')
            ->assertDontSee('No visits yet')
            ->assertSeeInOrder(['Dishes', '3 / 40', 'Categories', '1 / 8', 'Available Dishes', '2', 'Social Links', '1 / 1'])
            ->assertActionVisible('edit');
    }

    public function test_the_view_page_of_an_owner_mid_onboarding_hides_the_restaurant_sections(): void
    {
        $user = $this->userWithoutRestaurant();
        $user->forceFill(['onboarding_step' => 2])->save();
        $this->actingAs($this->admin());

        Livewire::test(ViewUser::class, ['record' => $user->getRouteKey()])
            ->assertOk()
            ->assertSee($user->email)
            ->assertSee('Not completed')
            ->assertSee('Step 2 of 6')
            ->assertDontSee('Menu link')
            ->assertDontSee('Traffic &amp; Analytics', false)
            ->assertDontSee('Total Views')
            ->assertDontSee('Available Dishes');
    }

    public function test_the_view_page_of_an_inactive_menu_without_visits_or_limits(): void
    {
        $restaurant = $this->ownerOn('custom', ['is_active' => false, 'phone' => null]);
        $this->actingAs($this->admin());

        Livewire::test(ViewUser::class, ['record' => $restaurant->user->getRouteKey()])
            ->assertOk()
            ->assertSee('Inactive')
            ->assertSee('No visits yet')
            ->assertSeeInOrder(['Top Device', '-'])
            ->assertSeeInOrder(['Dishes', '0 / -', 'Categories', '0 / -', 'Available Dishes', '0', 'Social Links', '0 / -']);
    }

    public function test_the_view_page_of_an_admin_shows_the_admin_role(): void
    {
        $admin = $this->admin();
        $other = User::factory()->admin()->create(['name' => 'Second Admin']);
        $this->actingAs($admin);

        Livewire::test(ViewUser::class, ['record' => $other->getRouteKey()])
            ->assertOk()
            ->assertSee('Second Admin')
            ->assertSee('admin')
            ->assertDontSee('menu_owner')
            ->assertDontSee('Traffic &amp; Analytics', false);
    }

    public function test_the_view_page_is_reachable_over_http_only_by_an_admin(): void
    {
        $owner = $this->owner()->user;

        $this->actingAs($owner)->get(UserResource::getUrl('view', ['record' => $owner]))->assertForbidden();
    }

    public function test_the_view_page_renders_over_http_for_an_admin(): void
    {
        $owner = $this->owner(['name' => ['en' => 'Byblos Bites']])->user;

        $this->actingAs($this->admin())
            ->get(UserResource::getUrl('view', ['record' => $owner]))
            ->assertOk()
            ->assertSee('Byblos Bites');
    }

    public function test_editing_a_user_with_a_new_password_hashes_and_saves_it(): void
    {
        $user = User::factory()->create();
        $old = $user->password;
        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertSchemaStateSet(['name' => $user->name, 'email' => $user->email, 'password' => null])
            ->fillForm(['password' => 'brand-new-secret'])
            ->call('save')
            ->assertHasNoFormErrors();

        $fresh = $user->fresh();
        $this->assertNotSame($old, $fresh->password);
        $this->assertNotSame('brand-new-secret', $fresh->password);
        $this->assertTrue(Hash::check('brand-new-secret', $fresh->password));
    }

    public function test_editing_a_user_can_promote_them_to_admin(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['role' => UserRole::Admin->value])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(UserRole::Admin, $user->fresh()->role);
    }

    public function test_editing_rejects_a_short_password_another_users_email_and_blanks(): void
    {
        $taken = User::factory()->create();
        $user = User::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['name' => '', 'email' => $taken->email, 'password' => 'short'])
            ->call('save')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'unique', 'password' => 'min']);

        $this->assertSame($user->email, $user->fresh()->email);
    }

    public function test_editing_keeps_the_users_own_email_valid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm(['email' => $user->email, 'name' => 'Same Email'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Same Email', $user->fresh()->name);
    }

    public function test_deleting_from_the_edit_page_removes_the_user_and_their_restaurant_for_good(): void
    {
        $restaurant = $this->owner();
        $user = $restaurant->user;
        $this->actingAs($this->admin());

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('delete')
            ->assertNotified('User deleted')
            ->assertRedirect(UserResource::getUrl('index'));

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('restaurants', ['id' => $restaurant->id]);
    }

    public function test_the_edit_page_hides_delete_on_your_own_account(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin);

        Livewire::test(EditUser::class, ['record' => $admin->getRouteKey()])
            ->assertActionHidden('delete');
    }

    public function test_creating_requires_a_password_and_defaults_the_role_to_owner(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateUser::class)
            ->assertSchemaStateSet(['role' => UserRole::MenuOwner->value])
            ->fillForm(['name' => 'No Pass', 'email' => 'nopass@example.com', 'password' => ''])
            ->call('create')
            ->assertHasFormErrors(['password' => 'required']);

        $this->assertDatabaseMissing('users', ['email' => 'nopass@example.com']);
    }

    public function test_creating_an_admin_stores_the_role_and_a_hashed_password(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Ops', 'email' => 'ops@example.com', 'password' => 'long-enough-pw', 'role' => UserRole::Admin->value])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::firstWhere('email', 'ops@example.com');
        $this->assertSame(UserRole::Admin, $created->role);
        $this->assertTrue(Hash::check('long-enough-pw', $created->password));
    }

    public function test_creating_rejects_a_bad_email_and_missing_fields(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => '', 'email' => 'not-an-email', 'password' => 'long-enough-pw', 'role' => null])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required', 'email' => 'email', 'role' => 'required']);
    }

    public function test_the_list_renders_every_column_for_owners_and_admins(): void
    {
        $admin = $this->admin();
        $onboarded = $this->owner(['name' => ['en' => 'Olive Tree']]);
        $onboarded->user->forceFill(['onboarding_completed_at' => now()->subDay()])->save();
        MenuSession::factory()->count(4)->for($onboarded)->create();
        $category = Category::factory()->create(['restaurant_id' => $onboarded->id]);
        Dish::factory()->count(2)->create(['restaurant_id' => $onboarded->id, 'category_id' => $category->id]);
        $inactive = Restaurant::factory()->inactive()->create();
        $pending = $this->userWithoutRestaurant();
        $pending->forceFill(['onboarding_step' => 3])->save();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertOk()
            ->assertCanSeeTableRecords([$admin, $onboarded->user, $inactive->user, $pending])
            ->assertTableColumnFormattedStateSet('role', 'Admin', $admin)
            ->assertTableColumnFormattedStateSet('role', 'Menu Owner', $onboarded->user)
            ->assertTableColumnStateSet('onboarding_completed_at', true, $onboarded->user)
            ->assertTableColumnStateSet('onboarding_completed_at', false, $pending)
            ->assertTableColumnStateSet('restaurant.name', 'Olive Tree', $onboarded->user)
            ->assertTableColumnStateSet('dishes_count', '2', $onboarded->user)
            ->assertTableColumnStateSet('dishes_count', '-', $pending)
            ->assertTableColumnStateSet('views_count', '4', $onboarded->user)
            ->assertTableColumnStateSet('views_count', '-', $pending)
            ->assertTableColumnFormattedStateSet('restaurant.is_active', 'Active', $onboarded->user)
            ->assertTableColumnFormattedStateSet('restaurant.is_active', 'Inactive', $inactive->user)
            ->assertSee('Completed 1 day ago')
            ->assertSee('Step 3 of 6')
            ->assertSee(route('filament.admin.resources.restaurants.edit', ['record' => $onboarded->id]), false);
    }

    public function test_the_list_hides_soft_deleted_users(): void
    {
        $gone = User::factory()->create();
        $gone->delete();
        $kept = User::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords([$kept])
            ->assertCanNotSeeTableRecords([$gone]);
    }

    public function test_the_list_searches_by_name_and_email(): void
    {
        $sami = User::factory()->create(['name' => 'Sami Haddad', 'email' => 'sami@example.com']);
        $rita = User::factory()->create(['name' => 'Rita Khoury', 'email' => 'rita@example.com']);
        $this->actingAs($this->admin());

        Livewire::test(ListUsers::class)
            ->searchTable('Haddad')
            ->assertCanSeeTableRecords([$sami])
            ->assertCanNotSeeTableRecords([$rita])
            ->searchTable('rita@')
            ->assertCanSeeTableRecords([$rita])
            ->assertCanNotSeeTableRecords([$sami]);
    }

    public function test_the_role_filter_keeps_only_that_role(): void
    {
        $admin = $this->admin();
        $owner = User::factory()->create();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->filterTable('role', UserRole::Admin->value)
            ->assertCanSeeTableRecords([$admin])
            ->assertCanNotSeeTableRecords([$owner])
            ->resetTableFilters()
            ->filterTable('role', UserRole::MenuOwner->value)
            ->assertCanSeeTableRecords([$owner])
            ->assertCanNotSeeTableRecords([$admin]);
    }

    public function test_the_setup_filters_split_completed_from_pending(): void
    {
        $done = User::factory()->create(['onboarding_completed_at' => now()]);
        $pending = User::factory()->create(['onboarding_completed_at' => null]);
        $this->actingAs($this->admin());

        Livewire::test(ListUsers::class)
            ->filterTable('onboarding_completed')
            ->assertCanSeeTableRecords([$done])
            ->assertCanNotSeeTableRecords([$pending])
            ->resetTableFilters()
            ->filterTable('onboarding_pending')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$done]);
    }

    public function test_the_joined_filters_look_at_the_signup_date(): void
    {
        $this->travelTo(now()->startOfWeek()->addDays(3)->setTime(12, 0));
        $today = User::factory()->create(['created_at' => now()]);
        $earlierThisWeek = User::factory()->create(['created_at' => now()->startOfWeek()->addHour()]);
        $lastMonth = User::factory()->create(['created_at' => now()->subMonth()]);
        $this->actingAs($this->admin());

        Livewire::test(ListUsers::class)
            ->filterTable('joined_today')
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$earlierThisWeek, $lastMonth])
            ->resetTableFilters()
            ->filterTable('joined_this_week')
            ->assertCanSeeTableRecords([$today, $earlierThisWeek])
            ->assertCanNotSeeTableRecords([$lastMonth]);
    }

    public function test_impersonate_is_offered_for_owners_only_and_never_for_yourself(): void
    {
        $admin = $this->admin();
        $otherAdmin = $this->admin();
        $owner = $this->owner()->user;
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertActionVisible(TestAction::make('impersonate')->table($owner))
            ->assertActionHasUrl(TestAction::make('impersonate')->table($owner), route('impersonate', $owner->id))
            ->assertActionHidden(TestAction::make('impersonate')->table($admin))
            ->assertActionHidden(TestAction::make('impersonate')->table($otherAdmin));
    }

    public function test_deleting_from_the_table_removes_the_user_for_good(): void
    {
        $restaurant = $this->owner();
        $user = $restaurant->user;
        $this->actingAs($this->admin());

        Livewire::test(ListUsers::class)
            ->callAction(TestAction::make('delete')->table($user))
            ->assertHasNoActionErrors();

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('restaurants', ['id' => $restaurant->id]);
    }

    public function test_one_admin_can_delete_another_from_the_table(): void
    {
        $admin = $this->admin();
        $other = $this->admin();
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)
            ->assertActionVisible(TestAction::make('delete')->table($other))
            ->callAction(TestAction::make('delete')->table($other));

        $this->assertDatabaseMissing('users', ['id' => $other->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_the_create_header_action_points_at_the_create_page(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListUsers::class)
            ->assertActionVisible(TestAction::make('create'))
            ->assertActionHasUrl(TestAction::make('create'), UserResource::getUrl('create'));
    }
}
