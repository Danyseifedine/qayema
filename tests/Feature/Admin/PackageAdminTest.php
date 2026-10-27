<?php

namespace Tests\Feature\Admin;

use App\Enums\Feature;
use App\Enums\PackageTier;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Packages\Pages\CreatePackage;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Packages\Pages\ListPackages;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_a_new_package_opens_on_the_default_packages_numbers(): void
    {
        $this->actingAs($this->admin());
        $free = Package::default();

        Livewire::test(CreatePackage::class)
            ->assertFormSet([
                'features.dish_limit' => 40,
                'features.analytics' => false,
                'ai_budget_cents' => 0.05,
                'tier' => 'bronze',
                'is_active' => true,
                'sort_order' => (int) Package::query()->max('sort_order') + 1,
            ]);
        $this->assertTrue($free->is_default);
    }

    public function test_an_admin_makes_a_package_that_owners_can_be_given(): void
    {
        $this->actingAs($this->admin());
        $this->get(PackageResource::getUrl('create'))->assertOk();

        Livewire::test(CreatePackage::class)
            ->fillForm([
                'name.en' => 'Starter',
                'name.ar' => 'المبتدئ',
                'slug' => 'starter',
                'price_cents' => 499,
                'tier' => 'silver',
                'features.dish_limit' => 80,
                'features.analytics' => true,
                'ai_budget_cents' => 0.75,
            ])
            ->call('create')
            ->assertHasNoFormErrors()
            ->assertNotified('Package created');

        $starter = Package::findBySlug('starter');
        $this->assertSame(['en' => 'Starter', 'ar' => 'المبتدئ'], $starter->getTranslations('name'));
        $this->assertSame([499, PackageTier::Silver, 80, 1, 7500, false], [
            $starter->price_cents, $starter->tier, $starter->featureValue(Feature::DishLimit),
            $starter->featureValue(Feature::Analytics), $starter->featureValue(Feature::WritingHelpCredits), $starter->is_default,
        ]);

        // On offer at once, and an owner may ask for it.
        $owner = $this->owner();
        $this->actingAs($owner->user)->getJson(route('api.packages.index'))->assertJsonFragment(['slug' => 'starter']);
    }

    /** @return array<string, array{0: string}> */
    public static function badSlugs(): array
    {
        return ['taken' => ['pro'], 'capitals' => ['Starter'], 'spaces' => ['my plan'], 'a dash at the end' => ['plan-'], 'none' => ['']];
    }

    #[DataProvider('badSlugs')]
    public function test_a_new_packages_slug_must_be_free_and_plain(string $slug): void
    {
        $this->actingAs($this->admin());

        Livewire::test(CreatePackage::class)
            ->fillForm(['name.en' => 'Plan', 'slug' => $slug])
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    public function test_a_saved_packages_slug_stays_as_it_is(): void
    {
        $this->actingAs($this->admin());
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->assertFormFieldIsDisabled('slug')
            ->fillForm(['slug' => 'changed'])
            ->call('save');

        $this->assertSame('pro', $pro->fresh()->slug);
    }

    public function test_another_package_made_the_default_lets_the_old_one_be_switched_off(): void
    {
        $this->actingAs($this->admin());
        $free = Package::findBySlug('free');
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $free->id])
            ->assertFormFieldIsDisabled('is_active')
            ->assertFormFieldIsDisabled('is_default');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['is_default' => true])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertTrue($pro->fresh()->is_default);
        $this->assertFalse($free->fresh()->is_default);
        $this->assertSame($pro->id, Package::default()->id);

        Livewire::test(EditPackage::class, ['record' => $free->id])
            ->assertFormFieldIsEnabled('is_active')
            ->fillForm(['is_active' => false])
            ->call('save');

        $this->assertFalse($free->fresh()->is_active);
        $this->assertTrue(PackageResource::canDelete($free->fresh()));
        $this->assertFalse(PackageResource::canDelete($pro->fresh()));
    }

    public function test_the_default_is_always_offered_and_only_one(): void
    {
        $pro = Package::findBySlug('pro');
        $pro->update(['is_active' => false]);

        $pro->update(['is_default' => true]);

        $this->assertTrue($pro->fresh()->is_active, 'Made the default, it is offered again.');
        $this->assertSame(1, Package::query()->where('is_default', true)->count());
    }

    public function test_editing_a_limit_reaches_every_restaurant_on_that_package(): void
    {
        $onFree = Restaurant::factory()->create(['template_id' => null]);
        $onPro = $this->ownerOn('pro');
        $this->assertSame(40, $onFree->limitOf(Feature::DishLimit));

        $this->actingAs($this->admin());
        Livewire::test(EditPackage::class, ['record' => Package::default()->id])
            ->fillForm(['features.dish_limit' => 75])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(75, $onFree->fresh()->limitOf(Feature::DishLimit));
        $this->assertSame(150, $onPro->fresh()->limitOf(Feature::DishLimit), 'Another package is untouched.');
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
        $this->assertNull($owner->fresh()->limitOf(Feature::DishLimit));
        $this->assertFalse($owner->fresh()->hasReached(Feature::DishLimit));
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
            ->assertTableColumnExists('is_active')
            ->assertTableColumnVisible('is_active')
            ->assertSee('Active')
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

        $this->assertSame('Pro (inactive)', $options[$pro->id]);
        $this->assertSame('Premium', $options[Package::query()->where('slug', 'premium')->value('id')]);
    }

    public function test_an_admin_writes_a_cards_own_lines(): void
    {
        $pro = Package::query()->where('slug', 'pro')->firstOrFail();
        $this->actingAs($this->admin());

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->fillForm(['highlights.en' => ['Room to grow', 'Your own colours'], 'highlights.ar' => ['مساحة للنمو']])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['Room to grow', 'Your own colours'], $pro->fresh()->highlightsIn('en'));
        $this->assertSame(['مساحة للنمو'], $pro->fresh()->highlightsIn('ar'));
    }
}
