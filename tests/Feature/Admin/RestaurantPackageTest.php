<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\FeatureGrantsRelationManager;
use App\Models\Package;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Assigning a package to one restaurant. This is how an owner actually moves
 * up: they ask, an admin sets it here.
 */
class RestaurantPackageTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_a_new_restaurant_defaults_to_the_free_package(): void
    {
        $owner = $this->owner();

        $this->assertSame('free', $owner->package->slug);
        $this->assertSame(40, $owner->dish_limit);
    }

    public function test_assigning_a_package_from_the_panel_raises_the_limits(): void
    {
        $owner = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $owner->id])
            ->fillForm(['package_id' => Package::findBySlug('premium')->id])
            ->call('save')
            ->assertHasNoFormErrors();

        $owner = $owner->fresh();
        $this->assertSame('premium', $owner->package->slug);
        $this->assertSame(500, $owner->dish_limit);
        $this->assertTrue($owner->entitlements()->can(Feature::QrStudio));
    }

    public function test_an_expiry_in_the_past_drops_the_restaurant_back_to_free(): void
    {
        $owner = $this->ownerOn('pro');
        $this->assertSame(150, $owner->dish_limit);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $owner->id])
            ->fillForm(['package_started_at' => now()->subMonth(), 'duration' => 'until', 'package_ends_at' => now()->subDay()])
            ->call('save')
            ->assertHasNoFormErrors();

        $owner = $owner->fresh();
        $this->assertTrue($owner->packageExpired());
        $this->assertSame(40, $owner->dish_limit);
        $this->assertFalse($owner->entitlements()->can(Feature::Appearance));
    }

    public function test_a_grant_stacks_on_top_of_the_package(): void
    {
        $owner = $this->ownerOn('pro');
        $this->actingAs($this->admin());

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $owner,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'feature' => Feature::DishLimit->value,
                'value' => 30,
                'source' => 'admin',
            ])
            ->assertHasNoActionErrors();

        $this->assertSame(180, $owner->fresh()->dish_limit, '150 from Pro + 30 granted.');
    }
}
