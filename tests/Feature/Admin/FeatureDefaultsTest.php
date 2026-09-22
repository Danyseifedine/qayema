<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Enums\UserRole;
use App\Filament\Admin\Pages\ManageFeatureDefaults;
use App\Models\FeatureDefault;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FeatureDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_the_page_loads_the_current_defaults(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManageFeatureDefaults::class)
            ->assertOk()
            ->assertSchemaStateSet([
                'defaults.dish_limit' => 40,
                'defaults.category_limit' => 10,
                'defaults.social_link_limit' => 2,
                // A flag renders as a toggle, so it arrives as a boolean.
                'defaults.qr_studio' => false,
            ]);
    }

    public function test_saving_changes_the_limit_for_every_restaurant(): void
    {
        $restaurant = Restaurant::factory()->create();
        $other = Restaurant::factory()->create();

        // Resolve once so both restaurants have a cached package to invalidate.
        $this->assertSame(40, $restaurant->dish_limit);
        $this->assertSame(40, $other->dish_limit);

        $this->actingAs($this->admin());

        Livewire::test(ManageFeatureDefaults::class)
            ->fillForm([
                'defaults.dish_limit' => 75,
                'defaults.category_limit' => 10,
                'defaults.social_link_limit' => 2,
                'defaults.qr_studio' => false,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(75, FeatureDefault::for(Feature::DishLimit));
        $this->assertSame(75, $restaurant->fresh()->dish_limit);
        $this->assertSame(75, $other->fresh()->dish_limit, 'The cache is flushed for every restaurant, not just one.');
    }

    public function test_a_grant_still_stacks_on_the_new_default(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->featureGrants()->create([
            'feature' => Feature::DishLimit,
            'value' => 20,
            'source' => 'admin',
        ]);

        $this->actingAs($this->admin());

        Livewire::test(ManageFeatureDefaults::class)
            ->fillForm([
                'defaults.dish_limit' => 100,
                'defaults.category_limit' => 10,
                'defaults.social_link_limit' => 2,
                'defaults.qr_studio' => false,
            ])
            ->call('save');

        $this->assertSame(120, $restaurant->fresh()->dish_limit, '100 default + 20 granted.');
    }

    public function test_turning_a_flag_on_unlocks_it_for_everyone(): void
    {
        $restaurant = Restaurant::factory()->create();
        $this->assertFalse($restaurant->package()->can(Feature::QrStudio));

        $this->actingAs($this->admin());

        Livewire::test(ManageFeatureDefaults::class)
            ->fillForm([
                'defaults.dish_limit' => 40,
                'defaults.category_limit' => 10,
                'defaults.social_link_limit' => 2,
                'defaults.qr_studio' => true,
            ])
            ->call('save');

        $this->assertTrue($restaurant->fresh()->package()->can(Feature::QrStudio));
    }

    public function test_a_negative_limit_is_rejected(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ManageFeatureDefaults::class)
            ->fillForm([
                'defaults.dish_limit' => -5,
                'defaults.category_limit' => 10,
                'defaults.social_link_limit' => 2,
                'defaults.qr_studio' => false,
            ])
            ->call('save')
            ->assertHasFormErrors(['defaults.dish_limit']);

        $this->assertSame(40, FeatureDefault::for(Feature::DishLimit));
    }

    public function test_an_owner_cannot_reach_the_admin_panel(): void
    {
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);

        $this->actingAs($owner)
            ->get(ManageFeatureDefaults::getUrl())
            ->assertForbidden();
    }
}
