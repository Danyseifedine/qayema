<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\FeatureGrantsRelationManager;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeatureGrantsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_granting_slots_raises_only_that_restaurants_limit(): void
    {
        $restaurant = Restaurant::factory()->create();
        $untouched = Restaurant::factory()->create();

        $this->actingAs($this->admin());

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'feature' => Feature::DishLimit->value,
                'value' => 60,
                'source' => 'admin',
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('feature_grants', [
            'restaurant_id' => $restaurant->id,
            'feature' => Feature::DishLimit->value,
            'value' => 60,
            'source' => 'admin',
        ]);

        $this->assertSame(100, $restaurant->fresh()->limitOf(Feature::DishLimit), '40 default + 60 granted.');
        $this->assertSame(40, $untouched->fresh()->limitOf(Feature::DishLimit), 'Other restaurants are unaffected.');
    }

    public function test_granting_a_flag_unlocks_it_for_that_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();

        $this->actingAs($this->admin());

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'feature' => Feature::QrStudio->value,
                'value' => 1,
                'source' => 'admin',
            ]);

        $this->assertTrue($restaurant->fresh()->entitlements()->can(Feature::QrStudio));
    }

    public function test_an_override_turns_a_feature_off_for_that_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create(['package_id' => Package::findBySlug('premium')->id]);
        $this->actingAs($this->admin());

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'feature' => Feature::Ordering->value,
                'mode' => GrantMode::Set->value,
                'state' => 0,
                'note' => 'Kitchen closed for renovation',
            ])
            ->assertHasNoActionErrors();

        $grant = FeatureGrant::query()->sole();
        $this->assertSame(GrantMode::Set, $grant->mode);
        $this->assertSame(0, $grant->value);
        $this->assertFalse($restaurant->fresh()->entitlements()->can(Feature::Ordering));
        $this->assertSame('Turned off', FeatureGrantsRelationManager::describe($grant));
    }

    public function test_an_override_sets_a_limit_to_unlimited_and_edits_back_to_a_number(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->actingAs($this->admin());

        $manager = Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'feature' => Feature::DishLimit->value,
                'mode' => GrantMode::Set->value,
                'unlimited' => true,
            ])
            ->assertHasNoActionErrors();

        $grant = FeatureGrant::query()->sole();
        $this->assertNull($grant->value);
        $this->assertNull($restaurant->fresh()->limitOf(Feature::DishLimit));
        $this->assertSame('Set to unlimited', FeatureGrantsRelationManager::describe($grant));

        $manager
            ->mountAction(TestAction::make('edit')->table($grant))
            ->assertActionDataSet(['unlimited' => true])
            ->setActionData(['unlimited' => false, 'value' => 12])
            ->callMountedAction()
            ->assertHasNoActionErrors();

        $this->assertSame(12, $restaurant->fresh()->limitOf(Feature::DishLimit));
        $this->assertSame('Set to 12', FeatureGrantsRelationManager::describe($grant->fresh()));
    }

    public function test_each_grant_says_what_it_does(): void
    {
        $restaurant = Restaurant::factory()->create();
        $describe = fn (FeatureGrant $grant): string => FeatureGrantsRelationManager::describe($grant);

        $this->assertSame('+5', $describe(FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 5)->create()));
        $this->assertSame('Unlocked', $describe(FeatureGrant::factory()->for($restaurant)->forFeature(Feature::QrStudio)->create()));
        $this->assertSame('Turned on', $describe(FeatureGrant::factory()->for($restaurant)->setTo(Feature::Analytics, 1)->create()));
    }

    public function test_the_table_lists_the_restaurants_grants(): void
    {
        $restaurant = Restaurant::factory()->create();
        $grant = $restaurant->featureGrants()->create([
            'feature' => Feature::CategoryLimit,
            'value' => 5,
            'source' => 'admin',
        ]);

        $this->actingAs($this->admin());

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->assertOk()
            ->assertCanSeeTableRecords([$grant]);
    }

    public function test_revoking_a_grant_lowers_the_limit_again(): void
    {
        $restaurant = Restaurant::factory()->create();
        $grant = $restaurant->featureGrants()->create([
            'feature' => Feature::DishLimit,
            'value' => 60,
            'source' => 'admin',
        ]);

        $this->assertSame(100, $restaurant->limitOf(Feature::DishLimit));

        $this->actingAs($this->admin());

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('delete')->table($grant));

        $this->assertSame(40, $restaurant->fresh()->limitOf(Feature::DishLimit));
    }
}
