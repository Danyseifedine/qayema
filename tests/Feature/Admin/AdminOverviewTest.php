<?php

namespace Tests\Feature\Admin;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Widgets\Overview;
use App\Models\ContactMessage;
use App\Models\Package;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * How the admin finds things: the home's overview figures, the menu's
 * order and groups, the top bar's search, and the Users list's tabs.
 */
class AdminOverviewTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_overview_counts_restaurants_payers_income_and_the_inbox(): void
    {
        $this->ownerOn('pro', ['pays_with_whish' => true, 'is_active' => true]);
        $this->ownerOn('premium', ['price_cents' => 1500, 'is_active' => true]);
        $this->owner(['package_id' => Package::findBySlug('free')->id, 'is_active' => false, 'created_at' => now()->subMonth()]);
        // Switched off: it leaves the payers and the month's income.
        $this->ownerOn('premium', ['pays_with_whish' => true, 'is_active' => false, 'created_at' => now()->subMonth()]);
        ContactMessage::factory()->packageRequest()->create();
        ContactMessage::factory()->create();

        Livewire::test(Overview::class)
            ->assertSeeInOrder(['Restaurants', '4', '2 menus online · 2 new this week'])
            ->assertSeeInOrder(['Paying', '2', '1 through Whish'])
            // Pro's $7.99 and the deal's $15.00.
            ->assertSeeInOrder(['A month', '$22.99'])
            ->assertSeeInOrder(['Inbox', '2', '1 package request']);
    }

    public function test_an_empty_overview_says_nothing_is_waiting(): void
    {
        Livewire::test(Overview::class)
            ->assertSee('$0.00')
            ->assertSee('Nothing waiting');
    }

    public function test_the_home_shows_the_overview_first(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(Overview::class);
    }

    public function test_the_menu_puts_the_daily_work_first_then_menu_content_then_settings(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))->assertOk();

        $groups = collect(Filament::getNavigation())->mapWithKeys(fn (NavigationGroup $group): array => [
            (string) ($group->getLabel() ?? '') => collect($group->getItems())->map(fn (NavigationItem $item): string => (string) $item->getLabel())->values()->all(),
        ])->all();

        $this->assertSame(['', 'Menu content', 'Settings'], array_keys($groups));
        $this->assertSame(['Dashboard', 'Restaurants', 'Inbox', 'Users'], $groups['']);
        $this->assertSame(['Dishes', 'Categories', 'Social links', 'Offers'], $groups['Menu content']);
        $this->assertSame(['Packages', 'AI credits', 'Templates', 'Blocked IPs'], $groups['Settings']);
    }

    public function test_the_top_bar_finds_a_restaurant_by_its_link_with_its_owner(): void
    {
        $restaurant = $this->ownerOn('pro', ['slug' => 'cedar-corner']);

        $results = RestaurantResource::getGlobalSearchResults('cedar-corner');

        $this->assertCount(1, $results);
        $this->assertSame([
            'Link' => 'cedar-corner',
            'Owner' => $restaurant->user->email,
            'Package' => 'Pro',
        ], $results->first()->details);
    }

    public function test_the_top_bar_finds_a_user_by_username(): void
    {
        $user = User::factory()->create(['email' => null, 'username' => 'karim.h']);

        $results = UserResource::getGlobalSearchResults('karim.h');

        $this->assertCount(1, $results);
        $this->assertSame(['Sign-in' => 'karim.h'], $results->first()->details);
        $this->assertSame((string) $user->name, (string) $results->first()->title);
    }

    public function test_the_users_tabs_split_owners_admins_and_unfinished_setups(): void
    {
        $done = $this->owner()->user;
        $done->forceFill(['onboarding_completed_at' => now()])->save();
        $midway = User::factory()->create(['onboarding_completed_at' => null, 'onboarding_step' => 2]);
        $admin = User::factory()->create(['role' => UserRole::Admin]);

        Livewire::test(ListUsers::class)
            ->set('activeTab', 'owners')
            ->assertCanSeeTableRecords([$done, $midway])
            ->assertCanNotSeeTableRecords([$admin])
            ->set('activeTab', 'setup')
            ->assertCanSeeTableRecords([$midway])
            ->assertCanNotSeeTableRecords([$done, $admin])
            ->set('activeTab', 'admins')
            ->assertCanSeeTableRecords([$admin])
            ->assertCanNotSeeTableRecords([$done, $midway]);
    }
}
