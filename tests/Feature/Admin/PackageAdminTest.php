<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
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

    public function test_packages_cannot_be_created_in_the_admin(): void
    {
        // They come from config/package.php; deleting one is in AdminDeleteTest.
        $this->assertFalse(PackageResource::canCreate());
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
        $this->assertSame(150, $onPro->fresh()->dish_limit, 'Another package is untouched.');
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

    public function test_a_limit_the_package_does_not_carry_yet_opens_on_its_default_not_as_unlimited(): void
    {
        $package = Package::findBySlug('pro');
        $package->forceFill(['features' => collect($package->features)->except('category_limit')->all()])->save();
        $this->actingAs($this->admin());

        Livewire::test(EditPackage::class, ['record' => $package->id])
            ->assertFormSet(['features.category_limit' => Feature::CategoryLimit->defaultValue()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(Feature::CategoryLimit->defaultValue(), $package->fresh()->features['category_limit']);
    }

    public function test_marking_a_package_most_popular_takes_it_off_the_others(): void
    {
        $pro = Package::findBySlug('pro');
        $this->actingAs($this->admin());

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['is_featured' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($pro->fresh()->is_featured);
        $this->assertSame(['pro'], Package::query()->where('is_featured', true)->pluck('slug')->all());
    }

    public function test_an_admin_takes_a_package_off_offer_from_the_list(): void
    {
        $pro = Package::query()->where('slug', 'pro')->firstOrFail();
        $this->actingAs($this->admin());

        Livewire::test(ListPackages::class)
            ->call('updateTableColumnState', 'is_active', (string) $pro->getKey(), false);

        $this->assertFalse($pro->fresh()->is_active);
    }

    /** Every restaurant falls back on it, so it is always on offer. */
    public function test_the_default_package_stays_on_offer(): void
    {
        $default = Package::default();

        $default->update(['is_active' => false]);

        $this->assertTrue($default->fresh()->is_active);
    }

    public function test_a_package_taken_off_offer_is_no_longer_most_popular(): void
    {
        $premium = Package::query()->where('slug', 'premium')->firstOrFail();
        $this->assertTrue($premium->is_featured);

        $premium->update(['is_active' => false]);

        $this->assertFalse($premium->fresh()->is_featured);
    }

    /** Still assignable by hand (a deal agreed one to one), and named as such. */
    public function test_the_package_picker_names_a_package_no_longer_offered(): void
    {
        $pro = Package::query()->where('slug', 'pro')->firstOrFail();
        $pro->update(['is_active' => false]);

        $options = PackageFields::packageOptions();

        $this->assertSame('Pro (not offered)', $options[$pro->id]);
        $this->assertSame('Premium', $options[Package::query()->where('slug', 'premium')->value('id')]);
    }
}
