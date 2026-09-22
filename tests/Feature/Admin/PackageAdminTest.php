<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class PackageAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_list_renders_for_an_admin(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(ListPackages::class)
            ->assertOk()
            ->assertCanSeeTableRecords(Package::query()->get());
    }

    public function test_an_owner_cannot_reach_the_packages_panel(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->get(PackageResource::getUrl('index'))->assertForbidden();
    }

    public function test_packages_can_be_neither_created_nor_deleted(): void
    {
        $this->assertFalse(PackageResource::canCreate());
        $this->assertFalse(PackageResource::canDeleteAny());
        $this->assertArrayNotHasKey('create', PackageResource::getPages());
    }

    public function test_editing_a_limit_reaches_every_restaurant_on_that_package(): void
    {
        $onFree = Restaurant::factory()->create(['template_id' => null]);
        $onPro = $this->ownerOn('pro');
        $this->assertSame(40, $onFree->dish_limit);

        $this->actingAs($this->admin());
        Livewire::test(EditPackage::class, ['record' => Package::default()->id])
            ->fillForm(['features.dish_limit' => 75])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(75, $onFree->fresh()->dish_limit);
        $this->assertSame(120, $onPro->fresh()->dish_limit, 'Another package is untouched.');
    }

    public function test_an_empty_limit_saves_as_unlimited(): void
    {
        $owner = $this->ownerOn('pro');
        $this->actingAs($this->admin());

        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('pro')->id])
            ->fillForm(['features.dish_limit' => null])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertNull(Package::findBySlug('pro')->featureValue(Feature::DishLimit));
        $this->assertNull($owner->fresh()->dish_limit);
        $this->assertFalse($owner->fresh()->hasReachedDishLimit());
    }

    public function test_a_flag_is_stored_as_zero_or_one(): void
    {
        $owner = $this->owner();
        $free = Package::default();
        $this->actingAs($this->admin());

        Livewire::test(EditPackage::class, ['record' => $free->id])
            ->fillForm(['features.qr_studio' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(1, $free->fresh()->features['qr_studio']);
        $this->assertTrue($owner->fresh()->entitlements()->can(Feature::QrStudio));

        Livewire::test(EditPackage::class, ['record' => $free->id])
            ->fillForm(['features.qr_studio' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(0, $free->fresh()->features['qr_studio']);
        $this->assertFalse($owner->fresh()->entitlements()->can(Feature::QrStudio));
    }

    public function test_the_edit_form_shows_an_unlimited_limit_as_empty(): void
    {
        $this->actingAs($this->admin());

        Livewire::test(EditPackage::class, ['record' => Package::findBySlug('custom')->id])
            ->assertFormSet(['features.dish_limit' => null, 'features.qr_studio' => true]);
    }
}
