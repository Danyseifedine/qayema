<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Admin\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\PackageChangesRelationManager;
use App\Filament\Admin\Resources\Templates\Pages\EditTemplate;
use App\Filament\Admin\Widgets\PackagesEndingSoon;
use App\Models\ContactMessage;
use App\Models\Package;
use App\Models\Template;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Everything an admin does to a restaurant's package: change it for a while
 * or forever, extend it, send it back to the default, move many at once,
 * apply a request, and read the history.
 */
class PackageManagementTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function pro(): Package
    {
        return Package::findBySlug('pro');
    }

    public function test_change_package_for_a_number_of_months(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->callAction(TestAction::make('changePackage')->table($restaurant), data: [
                'package_id' => $this->pro()->id,
                'package_started_at' => now(),
                'duration' => 'months',
                'months' => 3,
                'note' => 'Paid by bank transfer.',
            ])
            ->assertHasNoFormErrors();

        $restaurant = $restaurant->fresh();
        $this->assertSame('pro', $restaurant->package->slug);
        $this->assertTrue($restaurant->package_ends_at->isSameDay(now()->addMonthsNoOverflow(3)));
        $this->assertSame('Paid by bank transfer.', $restaurant->packageChanges()->first()->note);
    }

    public function test_change_package_forever(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => now()->addDay()]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->callAction(TestAction::make('changePackage')->table($restaurant), data: [
                'package_id' => Package::findBySlug('premium')->id,
                'package_started_at' => now(),
                'duration' => 'forever',
            ])
            ->assertHasNoFormErrors();

        $this->assertNull($restaurant->fresh()->package_ends_at);
        $this->assertSame(1000, $restaurant->fresh()->dish_limit);
    }

    public function test_an_end_before_the_start_is_refused(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->callAction(TestAction::make('changePackage')->table($restaurant), data: [
                'package_id' => $this->pro()->id,
                'package_started_at' => now(),
                'duration' => 'until',
                'package_ends_at' => now()->subDay(),
            ])
            ->assertHasFormErrors(['package_ends_at']);
    }

    public function test_extend_adds_months_to_the_end(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(5)]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->callAction(TestAction::make('extendPackage')->table($restaurant), data: ['extend_by' => 1])
            ->assertHasNoFormErrors();

        $this->assertTrue($restaurant->fresh()->package_ends_at->isSameDay(now()->addDays(5)->addMonthNoOverflow()));
    }

    public function test_a_package_that_runs_forever_has_nothing_to_extend(): void
    {
        $restaurant = $this->ownerOn('pro');
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->assertActionHidden(TestAction::make('extendPackage')->table($restaurant));
    }

    public function test_back_to_the_default_package(): void
    {
        $restaurant = $this->ownerOn('premium', ['package_ends_at' => now()->addYear()]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->callAction(TestAction::make('resetPackage')->table($restaurant), data: ['note' => 'Refunded.']);

        $this->assertSame('free', $restaurant->fresh()->package->slug);
        $this->assertNull($restaurant->fresh()->package_ends_at);
    }

    public function test_bulk_change_moves_every_selected_restaurant(): void
    {
        $first = $this->owner();
        $second = $this->owner();
        $untouched = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->selectTableRecords([$first->id, $second->id])
            ->callAction(TestAction::make('changePackages')->table()->bulk(), data: [
                'package_id' => $this->pro()->id,
                'package_started_at' => now(),
                'duration' => 'forever',
            ])
            ->assertHasNoFormErrors();

        $this->assertSame('pro', $first->fresh()->package->slug);
        $this->assertSame('pro', $second->fresh()->package->slug);
        $this->assertSame('free', $untouched->fresh()->package->slug);
    }

    public function test_the_table_filters_by_the_package_in_force_and_its_dates(): void
    {
        $ending = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(3)]);
        $forever = $this->ownerOn('pro');
        $lapsed = $this->ownerOn('pro', ['package_started_at' => now()->subYear(), 'package_ends_at' => now()->subDay()]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->filterTable('package', $this->pro()->id)
            ->assertCanSeeTableRecords([$ending, $forever])
            ->assertCanNotSeeTableRecords([$lapsed]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('package', Package::default()->id)
            ->assertCanSeeTableRecords([$lapsed])
            ->assertCanNotSeeTableRecords([$ending, $forever]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('package_status', 'ending_7')
            ->assertCanSeeTableRecords([$ending])
            ->assertCanNotSeeTableRecords([$forever, $lapsed]);

        Livewire::test(ListRestaurants::class)
            ->filterTable('package_status', 'expired')
            ->assertCanSeeTableRecords([$lapsed])
            ->assertCanNotSeeTableRecords([$ending, $forever]);
    }

    public function test_the_form_turns_months_into_an_end_date_and_keeps_the_note(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSchemaStateSet(['duration' => 'forever'])
            ->fillForm([
                'package_id' => $this->pro()->id,
                'package_started_at' => now(),
                'duration' => 'months',
                'months' => 6,
                'note' => 'Six months paid.',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $restaurant = $restaurant->fresh();
        $this->assertTrue($restaurant->package_ends_at->isSameDay(now()->addMonthsNoOverflow(6)));
        $this->assertSame('Six months paid.', $restaurant->packageChanges()->first()->note);
    }

    public function test_creating_a_restaurant_records_its_first_package(): void
    {
        $owner = User::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(CreateRestaurant::class)
            ->fillForm([
                'user_id' => $owner->id,
                'name.en' => 'Olive',
                'slug' => 'olive',
                'package_id' => $this->pro()->id,
                'package_started_at' => now(),
                'duration' => 'until',
                'package_ends_at' => now()->addMonth(),
                'note' => 'Launch offer.',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $restaurant = $owner->fresh()->restaurant;
        $this->assertSame('pro', $restaurant->package->slug);
        $this->assertSame('Launch offer.', $restaurant->packageChanges()->first()->note);
    }

    public function test_the_history_lists_every_change(): void
    {
        $restaurant = $this->owner();
        $this->actingAs($this->admin());
        $restaurant->packageChangeNote = 'Upgrade.';
        $restaurant->update(['package_id' => $this->pro()->id]);

        Livewire::test(PackageChangesRelationManager::class, [
            'ownerRecord' => $restaurant->fresh(),
            'pageClass' => EditRestaurant::class,
        ])
            ->assertCanSeeTableRecords($restaurant->packageChanges()->get())
            ->assertSee('Free → Pro')
            ->assertSee('Upgrade.');
    }

    public function test_the_ending_soon_widget_lists_what_ends_soon_and_what_just_ended(): void
    {
        $soon = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(10)]);
        $later = $this->ownerOn('pro', ['package_ends_at' => now()->addDays(60)]);
        $justEnded = $this->ownerOn('pro', ['package_started_at' => now()->subYear(), 'package_ends_at' => now()->subDays(3)]);
        $longGone = $this->ownerOn('pro', ['package_started_at' => now()->subYears(2), 'package_ends_at' => now()->subDays(90)]);
        $this->actingAs($this->admin());

        Livewire::test(PackagesEndingSoon::class)
            ->assertCanSeeTableRecords([$soon, $justEnded])
            ->assertCanNotSeeTableRecords([$later, $longGone])
            ->callAction(TestAction::make('extendPackage')->table($soon), data: ['extend_by' => 'forever']);

        $this->assertNull($soon->fresh()->package_ends_at);
    }

    public function test_a_package_request_is_applied_in_one_step(): void
    {
        $restaurant = $this->owner();
        $message = ContactMessage::query()->create([
            'name' => 'Owner',
            'email' => 'owner@example.com',
            'message' => 'Pro please',
            'ip_address' => '127.0.0.1',
            'user_id' => $restaurant->user_id,
            'package_id' => $this->pro()->id,
        ]);
        $this->actingAs($this->admin());

        Livewire::test(ViewContactMessage::class, ['record' => $message->id])
            ->assertActionVisible('applyPackage')
            ->callAction('applyPackage', data: ['duration' => 'months', 'months' => 12])
            ->assertHasNoFormErrors();

        $restaurant = $restaurant->fresh();
        $this->assertSame('pro', $restaurant->package->slug);
        $this->assertTrue($restaurant->package_ends_at->isSameDay(now()->addYear()));
        $this->assertStringStartsWith('Requested on', $restaurant->packageChanges()->first()->note);
    }

    public function test_a_plain_contact_message_has_nothing_to_apply(): void
    {
        $message = ContactMessage::query()->create(['name' => 'Guest', 'email' => 'g@example.com', 'message' => 'Hi', 'ip_address' => '127.0.0.1']);
        $this->actingAs($this->admin());

        Livewire::test(ViewContactMessage::class, ['record' => $message->id])
            ->assertActionHidden('applyPackage');
    }

    public function test_a_design_can_be_marked_premium(): void
    {
        $template = Template::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->fillForm(['is_premium' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($template->fresh()->is_premium);
    }

    public function test_the_form_edits_the_english_name_and_keeps_the_other_language(): void
    {
        $restaurant = $this->owner(['name' => ['en' => 'Olive', 'ar' => 'زيتون'], 'description' => ['en' => 'Fresh', 'ar' => 'طازج']]);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSchemaStateSet(['name.en' => 'Olive', 'description.en' => 'Fresh'])
            ->fillForm(['name.en' => 'Olive Tree', 'duration' => 'forever'])
            ->call('save')
            ->assertHasNoFormErrors();

        $restaurant = $restaurant->fresh();
        $this->assertSame(['en' => 'Olive Tree', 'ar' => 'زيتون'], $restaurant->getTranslations('name'));
        $this->assertSame(['en' => 'Fresh', 'ar' => 'طازج'], $restaurant->getTranslations('description'));
    }
}
