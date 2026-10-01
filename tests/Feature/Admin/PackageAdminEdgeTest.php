<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Models\Package;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The packages list and edit form beyond the everyday edits: how prices and
 * feature values read in the table, what cannot be done to a package, and
 * what the form refuses.
 */
class PackageAdminEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_the_price_column_reads_free_and_a_price(): void
    {
        Livewire::test(ListPackages::class)
            ->assertTableColumnFormattedStateSet('price_cents', 'Free', Package::findBySlug('free'))
            ->assertTableColumnFormattedStateSet('price_cents', '12.00', Package::findBySlug('pro'))
            ->assertTableColumnFormattedStateSet('price_cents', '29.00', Package::findBySlug('premium'));
    }

    /**
     * Regression: a package without a price (Custom) rendered an empty cell,
     * because Filament never formats a blank state. It reads "Contact us",
     * as the edit form's helper text promises.
     */
    public function test_a_package_without_a_price_reads_contact_us_in_the_table(): void
    {
        $this->assertNull(Package::findBySlug('custom')->price_cents);

        Livewire::test(ListPackages::class)
            ->assertSee('Contact us');
    }

    public function test_the_feature_columns_read_limits_unlimited_and_flags(): void
    {
        $free = Package::findBySlug('free');
        $custom = Package::findBySlug('custom');

        Livewire::test(ListPackages::class)
            ->assertTableColumnStateSet('feature_dish_limit', '40', $free)
            ->assertTableColumnStateSet('feature_dish_limit', '∞', $custom)
            ->assertTableColumnStateSet('feature_qr_studio', false, $free)
            ->assertTableColumnStateSet('feature_qr_studio', true, $custom);
    }

    public function test_the_restaurants_column_counts_who_is_on_each_package(): void
    {
        $this->ownerOn('pro');
        $this->ownerOn('pro');
        $this->owner();

        Livewire::test(ListPackages::class)
            ->assertTableColumnStateSet('restaurants_count', 2, Package::findBySlug('pro'))
            ->assertTableColumnStateSet('restaurants_count', 1, Package::findBySlug('free'))
            ->assertTableColumnStateSet('restaurants_count', 0, Package::findBySlug('premium'));
    }

    public function test_the_list_is_ordered_by_sort_order_and_searchable_by_name(): void
    {
        $ordered = Package::query()->orderBy('sort_order')->get();

        Livewire::test(ListPackages::class)
            ->assertCanSeeTableRecords($ordered, inOrder: true)
            ->searchTable('Premium')
            ->assertCanSeeTableRecords([Package::findBySlug('premium')])
            ->assertCanNotSeeTableRecords([Package::findBySlug('free'), Package::findBySlug('pro')]);
    }

    public function test_only_the_default_package_is_kept_from_deletion(): void
    {
        // Every restaurant falls back on the default; any other one may go.
        $pro = Package::findBySlug('pro');
        $default = Package::default();

        $this->assertTrue(PackageResource::canDelete($pro));
        $this->assertFalse(PackageResource::canDelete($default));

        Livewire::test(ListPackages::class)
            ->assertActionVisible(TestAction::make('delete')->table($pro))
            ->assertActionHidden(TestAction::make('delete')->table($default))
            ->assertActionVisible(TestAction::make('edit')->table($pro));

        Livewire::test(EditPackage::class, ['record' => $pro->id])->assertActionVisible('delete');
        Livewire::test(EditPackage::class, ['record' => $default->id])->assertActionHidden('delete');
    }

    public function test_the_edit_page_renders_over_http(): void
    {
        $this->get(PackageResource::getUrl('edit', ['record' => Package::findBySlug('premium')]))
            ->assertOk()
            ->assertSee('Premium');
    }

    public function test_the_edit_form_requires_the_english_name(): void
    {
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['name.en' => ''])
            ->call('save')
            ->assertHasFormErrors(['name.en']);

        $this->assertSame('Pro', $pro->fresh()->getTranslation('name', 'en'));
    }

    public function test_a_negative_limit_is_refused(): void
    {
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['features.dish_limit' => -5])
            ->call('save')
            ->assertHasFormErrors(['features.dish_limit']);

        $this->assertSame(150, $pro->fresh()->featureValue(Feature::DishLimit));
    }
}
