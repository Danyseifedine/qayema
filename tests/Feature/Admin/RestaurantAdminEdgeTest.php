<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\PackageChangesRelationManager;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Models\MenuSession;
use App\Models\Package;
use App\Models\PackageChange;
use App\Models\Restaurant;
use App\Models\Template;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The restaurants resource beyond the package actions: every table column
 * and filter with real rows, the edit and create forms' edge cases, the
 * "Log in as owner" action and the package history.
 */
class RestaurantAdminEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_list_renders_every_column_for_a_restaurant_with_traffic(): void
    {
        $restaurant = $this->ownerOn('pro', ['name' => ['en' => 'Busy Bistro'], 'package_ends_at' => now()->addDays(3)]);
        MenuSession::factory()->count(2)->create(['restaurant_id' => $restaurant->id]);
        MenuSession::factory()->viaQr()->create(['restaurant_id' => $restaurant->id]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->assertCanSeeTableRecords([$restaurant])
            ->assertTableColumnStateSet('dish_limit', '150', $restaurant)
            ->assertTableColumnStateSet('unique_visitors', 3, $restaurant)
            ->assertTableColumnStateSet('qr_scans', 1, $restaurant)
            ->assertSee('Busy Bistro')
            ->assertSee('Until '.now()->addDays(3)->toFormattedDateString());
    }

    public function test_the_logo_is_described_and_not_a_second_link(): void
    {
        Storage::fake('public');
        $restaurant = $this->owner(['name' => ['en' => 'Busy Bistro']]);
        $restaurant->addMedia(UploadedFile::fake()->image('logo.png', 64, 64))->toMediaCollection('logo');
        $this->actingAs($this->admin());

        $html = Livewire::test(ListRestaurants::class)->html();

        $this->assertStringContainsString('alt="Logo of Busy Bistro"', $html);
        $this->assertDoesNotMatchRegularExpression('/<a[^>]*fi-ta-col[^>]*>\s*<div[^>]*fi-ta-image/', $html);
    }

    public function test_an_unlimited_dish_limit_shows_as_infinity(): void
    {
        $restaurant = $this->ownerOn('custom');
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->assertTableColumnStateSet('dish_limit', '∞', $restaurant);
    }

    public function test_the_package_badge_describes_scheduled_and_ended_packages(): void
    {
        $scheduled = $this->ownerOn('pro', ['package_started_at' => now()->addDays(5), 'package_ends_at' => null]);
        $ended = $this->ownerOn('pro', ['package_started_at' => now()->subYear(), 'package_ends_at' => now()->subDays(2)]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->assertCanSeeTableRecords([$scheduled, $ended])
            ->assertSee('Starts '.now()->addDays(5)->toFormattedDateString())
            ->assertSee('Ended '.now()->subDays(2)->toFormattedDateString().', on Free');
    }

    public function test_the_package_dates_filter_covers_in_force_forever_thirty_days_and_scheduled(): void
    {
        $inSevenDays = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(5)]);
        $inTwentyDays = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(20)]);
        $forever = $this->ownerOn('pro');
        $scheduled = $this->ownerOn('pro', ['package_started_at' => now()->addWeek()]);
        $lapsed = $this->ownerOn('pro', ['package_started_at' => now()->subYear(), 'package_ends_at' => now()->subDay()]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->filterTable('package_status', 'active')
            ->assertCanSeeTableRecords([$inSevenDays, $inTwentyDays, $forever])
            ->assertCanNotSeeTableRecords([$scheduled, $lapsed]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('package_status', 'forever')
            ->assertCanSeeTableRecords([$forever])
            ->assertCanNotSeeTableRecords([$inSevenDays, $inTwentyDays, $scheduled, $lapsed]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('package_status', 'ending_30')
            ->assertCanSeeTableRecords([$inSevenDays, $inTwentyDays])
            ->assertCanNotSeeTableRecords([$forever, $scheduled, $lapsed]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('package_status', 'scheduled')
            ->assertCanSeeTableRecords([$scheduled])
            ->assertCanNotSeeTableRecords([$inSevenDays, $inTwentyDays, $forever, $lapsed]);
    }

    public function test_the_status_owner_template_and_traffic_filters(): void
    {
        $design = Template::factory()->create();
        $active = $this->owner(['template_id' => $design->id]);
        $inactive = $this->owner(['is_active' => false, 'template_id' => null]);
        MenuSession::factory()->create(['restaurant_id' => $active->id]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->filterTable('is_active', 0)
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('user_id', $inactive->user_id)
            ->assertCanSeeTableRecords([$inactive])
            ->assertCanNotSeeTableRecords([$active]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('template_id', $design->id)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('has_views')
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$inactive]);
    }

    public function test_the_created_today_and_this_week_filters(): void
    {
        $this->travelTo(now()->startOfWeek()->addDays(3)->setTime(12, 0));
        $today = $this->owner();
        $lastMonth = $this->owner();
        $lastMonth->forceFill(['created_at' => now()->subMonth()])->save();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->filterTable('created_today')
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$lastMonth]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('created_this_week')
            ->assertCanSeeTableRecords([$today])
            ->assertCanNotSeeTableRecords([$lastMonth]);
    }

    public function test_the_active_toggle_in_the_table_switches_the_menu_off(): void
    {
        $restaurant = $this->owner();
        $this->assertTrue($restaurant->is_active);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->call('updateTableColumnState', 'is_active', (string) $restaurant->getKey(), false);

        $this->assertFalse($restaurant->fresh()->is_active);
    }

    public function test_bulk_delete_removes_only_the_selected_restaurants(): void
    {
        $first = $this->owner();
        $second = $this->owner();
        $kept = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('delete')->table()->bulk());

        $this->assertDatabaseMissing('restaurants', ['id' => $first->id]);
        $this->assertDatabaseMissing('restaurants', ['id' => $second->id]);
        $this->assertDatabaseHas('restaurants', ['id' => $kept->id]);
    }

    public function test_log_in_as_owner_points_at_the_impersonation_route_for_a_menu_owner(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->assertActionVisible(TestAction::make('impersonate')->table($restaurant))
            ->assertActionHasUrl(TestAction::make('impersonate')->table($restaurant), route('impersonate', $restaurant->user_id));

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertActionVisible('impersonate')
            ->assertActionHasUrl('impersonate', route('impersonate', $restaurant->user_id));
    }

    public function test_log_in_as_owner_is_hidden_when_the_restaurant_belongs_to_an_admin(): void
    {
        $adminOwned = Restaurant::factory()->create(['user_id' => User::factory()->admin()->create()->id]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->assertActionHidden(TestAction::make('impersonate')->table($adminOwned));

        Livewire::test(EditRestaurant::class, ['record' => $adminOwned->id])
            ->assertActionHidden('impersonate');
    }

    public function test_following_log_in_as_owner_signs_the_admin_in_as_the_owner(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        $this->get(route('impersonate', $restaurant->user_id))->assertRedirect('/');

        $this->assertAuthenticatedAs($restaurant->user);
    }

    public function test_the_edit_page_opens_on_the_restaurants_own_state(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => now()->addMonth(), 'slug' => 'olive-grove']);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertOk()
            ->assertSchemaStateSet([
                'slug' => 'olive-grove',
                'package_id' => Package::findBySlug('pro')->id,
                'duration' => 'until',
            ]);
    }

    public function test_the_edit_form_refuses_a_slug_already_taken(): void
    {
        $this->owner(['slug' => 'taken-slug']);
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['slug' => 'taken-slug'])
            ->call('save')
            ->assertHasFormErrors(['slug']);

        $this->assertNotSame('taken-slug', $restaurant->fresh()->slug);
    }

    public function test_renaming_a_restaurant_keeps_its_address(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Olive Grove'], 'slug' => 'olive-grove']);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['name.en' => 'Olive Grove Café'])
            ->assertSchemaStateSet(['slug' => 'olive-grove'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('olive-grove', $restaurant->fresh()->slug);
        $this->assertSame('Olive Grove Café', $restaurant->fresh()->getTranslation('name', 'en'));
    }

    public function test_a_new_restaurant_takes_its_address_from_its_name(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurant::class)
            ->fillForm(['name.en' => 'Olive Grove'])
            ->assertSchemaStateSet(['slug' => 'olive-grove']);
    }

    public function test_the_edit_form_requires_an_english_name(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Kept']]);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->fillForm(['name.en' => ''])
            ->call('save')
            ->assertHasFormErrors(['name.en']);

        $this->assertSame('Kept', $restaurant->fresh()->getTranslation('name', 'en'));
    }

    public function test_the_delete_header_action_removes_the_restaurant(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->callAction('delete');

        $this->assertDatabaseMissing('restaurants', ['id' => $restaurant->id]);
        $this->assertDatabaseHas('users', ['id' => $restaurant->user_id]);
    }

    public function test_create_requires_an_owner_a_name_and_a_slug(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurant::class)
            ->fillForm(['user_id' => null, 'name.en' => '', 'slug' => ''])
            ->call('create')
            ->assertHasFormErrors(['user_id' => 'required', 'name.en' => 'required']);

        $this->assertDatabaseCount('restaurants', 0);
    }

    public function test_the_create_page_renders(): void
    {
        $this->actingAs($this->admin());

        $this->get(RestaurantResource::getUrl('create'))->assertOk();
    }

    public function test_the_history_names_a_first_package_and_a_system_change(): void
    {
        $restaurant = $this->owner();
        $first = PackageChange::factory()->create([
            'restaurant_id' => $restaurant->id,
            'from_package_id' => null,
            'to_package_id' => Package::default()->id,
            'created_at' => now()->subMonth(),
        ]);
        $upgrade = PackageChange::factory()->create([
            'restaurant_id' => $restaurant->id,
            'ends_at' => now()->addMonth(),
            'note' => 'Paid cash.',
        ]);
        $this->actingAs($this->admin());

        Livewire::test(PackageChangesRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->assertCanSeeTableRecords([$first, $upgrade], inOrder: false)
            ->assertTableColumnStateSet('change', 'Created on Free', $first)
            ->assertTableColumnStateSet('change', 'Free → Pro', $upgrade)
            ->assertSee('System')
            ->assertSee('Forever')
            ->assertSee('Paid cash.');
    }

    public function test_the_history_can_be_trimmed_but_never_written_by_hand(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        $manager = Livewire::test(PackageChangesRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ]);

        // Lines come only from the restaurant's own save hook.
        $manager->assertActionDoesNotExist(TestAction::make('create')->table());
        $line = $restaurant->packageChanges()->first();
        $manager->assertActionDoesNotExist(TestAction::make('edit')->table($line))
            ->assertActionVisible(TestAction::make('delete')->table($line));
    }
}
