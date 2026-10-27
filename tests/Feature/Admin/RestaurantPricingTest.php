<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Models\Package;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class RestaurantPricingTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_restaurant_page_sets_its_own_price_and_whish(): void
    {
        $owner = $this->owner(['package_id' => Package::findBySlug('premium')->id]);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $owner->id])
            ->assertFormSet(['price_cents' => null, 'pays_with_whish' => false])
            ->fillForm(['price_cents' => '6.50', 'pays_with_whish' => true, 'note' => 'Friend discount'])
            ->call('save')
            ->assertHasNoFormErrors();

        $owner = $owner->fresh();
        $this->assertSame(650, $owner->price_cents);
        $this->assertTrue($owner->pays_with_whish);
        $this->assertSame('Friend discount', $owner->packageChanges()->latest('id')->first()->note);

        // Shown again in the currency, and cleared back to the package's price.
        Livewire::test(EditRestaurant::class, ['record' => $owner->id])
            ->assertFormSet(['price_cents' => '6.50'])
            ->fillForm(['price_cents' => ''])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull($owner->fresh()->price_cents);
    }

    public function test_the_price_box_is_saved_as_left_when_the_package_changes_here(): void
    {
        $owner = $this->owner(['package_id' => Package::findBySlug('premium')->id, 'price_cents' => 500]);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $owner->id])
            ->fillForm(['package_id' => Package::findBySlug('pro')->id])
            ->assertFormSet(['price_cents' => '5.00'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(500, $owner->fresh()->price_cents, 'What the box showed is what is saved.');
    }

    public function test_a_negative_price_is_refused(): void
    {
        $owner = $this->owner();
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $owner->id])
            ->fillForm(['price_cents' => '-1'])
            ->call('save')
            ->assertHasFormErrors(['price_cents']);
    }

    public function test_the_list_shows_the_price_and_finds_custom_prices_and_whish(): void
    {
        $premium = Package::findBySlug('premium')->id;
        $custom = $this->owner(['package_id' => $premium, 'price_cents' => 600, 'pays_with_whish' => true]);
        $plain = $this->owner(['package_id' => $premium]);
        $this->actingAs($this->admin());

        Livewire::test(ListRestaurants::class)
            ->assertTableColumnFormattedStateSet('price_cents', '$6.00', $custom)
            ->assertTableColumnFormattedStateSet('price_cents', '$10.00', $plain)
            ->filterTable('custom_price')
            ->assertCanSeeTableRecords([$custom])
            ->assertCanNotSeeTableRecords([$plain])
            ->resetTableFilters()
            ->filterTable('pays_with_whish', false)
            ->assertCanSeeTableRecords([$plain])
            ->assertCanNotSeeTableRecords([$custom]);
    }
}
