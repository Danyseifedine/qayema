<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Enums\UserRole;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\FeatureGrantsRelationManager;
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

        $this->assertSame(100, $restaurant->fresh()->dish_limit, '40 default + 60 granted.');
        $this->assertSame(40, $untouched->fresh()->dish_limit, 'Other restaurants are unaffected.');
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

        $this->assertSame(100, $restaurant->dish_limit);

        $this->actingAs($this->admin());

        Livewire::test(FeatureGrantsRelationManager::class, [
            'ownerRecord' => $restaurant,
            'pageClass' => EditRestaurant::class,
        ])
            ->callAction(TestAction::make('delete')->table($grant));

        $this->assertSame(40, $restaurant->fresh()->dish_limit);
    }
}
