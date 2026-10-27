<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Models\Package;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The Restaurants list's quick tabs: to renew, ended, paying, Whish and
 * switched off, each the list an admin would otherwise build with filters.
 */
class RestaurantListTabsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->freezeTime();
        $this->actingAs($this->admin());
    }

    public function test_to_renew_is_ending_within_a_week_or_ended_and_still_online(): void
    {
        $endingSoon = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(5)]);
        $inGrace = $this->ownerOn('pro', ['package_ends_at' => now()->subDay()]);
        $later = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(20)]);
        $offline = $this->ownerOn('pro', ['package_ends_at' => now()->subDays(10)]);
        $forever = $this->ownerOn('pro');

        Livewire::test(ListRestaurants::class)
            ->set('activeTab', 'renew')
            ->assertCanSeeTableRecords([$endingSoon, $inGrace])
            ->assertCanNotSeeTableRecords([$later, $offline, $forever]);

        $this->assertSame('2', RestaurantResource::getNavigationBadge());
    }

    public function test_no_badge_when_nothing_needs_renewing(): void
    {
        $this->ownerOn('pro');

        $this->assertNull(RestaurantResource::getNavigationBadge());
    }

    public function test_ended_lists_every_ended_package(): void
    {
        $ended = $this->ownerOn('pro', ['package_ends_at' => now()->subDays(10)]);
        $running = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(10)]);

        Livewire::test(ListRestaurants::class)
            ->set('activeTab', 'ended')
            ->assertCanSeeTableRecords([$ended])
            ->assertCanNotSeeTableRecords([$running]);
    }

    public function test_paying_whish_and_switched_off(): void
    {
        $paying = $this->ownerOn('pro', ['pays_with_whish' => true]);
        $free = $this->owner(['package_id' => Package::findBySlug('free')->id, 'is_active' => false]);
        // On Pro but switched off: not paying.
        $off = $this->ownerOn('pro', ['is_active' => false]);

        Livewire::test(ListRestaurants::class)
            ->set('activeTab', 'paying')
            ->assertCanSeeTableRecords([$paying])
            ->assertCanNotSeeTableRecords([$free, $off])
            ->set('activeTab', 'whish')
            ->assertCanSeeTableRecords([$paying])
            ->assertCanNotSeeTableRecords([$free])
            ->set('activeTab', 'off')
            ->assertCanSeeTableRecords([$free, $off])
            ->assertCanNotSeeTableRecords([$paying]);
    }

    public function test_a_row_keeps_open_menu_and_log_in_as_owner_behind_more(): void
    {
        $restaurant = $this->owner();

        Livewire::test(ListRestaurants::class)
            ->assertActionVisible(TestAction::make('menu')->table($restaurant))
            ->assertActionHasUrl(TestAction::make('menu')->table($restaurant), route('public.menu', $restaurant->slug))
            ->assertActionVisible(TestAction::make('impersonate')->table($restaurant));
    }

    public function test_the_price_says_when_it_is_its_own_and_paid_through_whish(): void
    {
        $both = $this->ownerOn('pro', ['price_cents' => 500, 'pays_with_whish' => true]);
        $whish = $this->ownerOn('pro', ['pays_with_whish' => true]);
        $plain = $this->ownerOn('pro');

        Livewire::test(ListRestaurants::class)
            ->assertTableColumnHasDescription('price_cents', 'Custom · Whish', $both)
            ->assertTableColumnHasDescription('price_cents', 'Whish', $whish)
            ->assertTableColumnDoesNotHaveDescription('price_cents', 'Whish', $plain);
    }
}
