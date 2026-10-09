<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Categories\Pages\CreateCategory;
use App\Filament\Admin\Resources\Categories\Pages\EditCategory;
use App\Filament\Admin\Resources\Categories\Pages\ListCategories;
use App\Filament\Admin\Resources\Dishes\Pages\CreateDish;
use App\Filament\Admin\Resources\Dishes\Pages\EditDish;
use App\Filament\Admin\Resources\Dishes\Pages\ListDishes;
use App\Filament\Admin\Resources\Packages\Pages\EditPackage;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Templates\Pages\CreateTemplate;
use App\Filament\Admin\Resources\Templates\Pages\EditTemplate;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Package;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Admin forms of translatable models edit one field per language and keep
 * every language they do not show. They used to bind the whole JSON to one
 * input, which showed "[object Object]" and could overwrite the text.
 */
class TranslatableFormsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->admin());
    }

    public function test_a_category_edits_its_english_and_keeps_the_other_language(): void
    {
        $category = Category::factory()->create([
            'restaurant_id' => $this->owner()->id,
            'name' => ['en' => 'Mains', 'ar' => 'أطباق رئيسية'],
            'description' => ['en' => 'From noon', 'ar' => 'من الظهر'],
        ]);

        Livewire::test(EditCategory::class, ['record' => $category->id])
            ->assertSchemaStateSet(['name.en' => 'Mains', 'description.en' => 'From noon'])
            ->assertDontSee('[object Object]')
            ->fillForm(['name.en' => 'Main dishes'])
            ->call('save')
            ->assertHasNoFormErrors();

        $category = $category->fresh();
        $this->assertSame(['en' => 'Main dishes', 'ar' => 'أطباق رئيسية'], $category->getTranslations('name'));
        $this->assertSame(['en' => 'From noon', 'ar' => 'من الظهر'], $category->getTranslations('description'));
    }

    public function test_a_category_is_created_in_english(): void
    {
        $restaurant = $this->owner();

        Livewire::test(CreateCategory::class)
            ->fillForm(['restaurant_id' => $restaurant->id, 'name.en' => 'Desserts', 'display_order' => 0])
            ->call('create')
            ->assertHasNoFormErrors();

        $category = Category::query()->where('restaurant_id', $restaurant->id)->sole();
        $this->assertSame(['en' => 'Desserts'], $category->getTranslations('name'));
        $this->assertSame([], $category->getTranslations('description'), 'A blank language is not stored.');
    }

    public function test_a_dish_edits_its_english_and_keeps_the_other_language(): void
    {
        $restaurant = $this->owner(['currency' => 'LBP']);
        $dish = Dish::factory()->create([
            'restaurant_id' => $restaurant->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $restaurant->id])->id,
            'name' => ['en' => 'Hummus', 'ar' => 'حمص'],
            'ingredients' => ['en' => 'Chickpeas', 'ar' => 'حمص حب'],
        ]);

        Livewire::test(EditDish::class, ['record' => $dish->id])
            ->assertSchemaStateSet(['name.en' => 'Hummus', 'ingredients.en' => 'Chickpeas'])
            ->assertSee('LBP')
            ->fillForm(['ingredients.en' => 'Chickpeas, tahini'])
            ->call('save')
            ->assertHasNoFormErrors();

        $dish = $dish->fresh();
        $this->assertSame(['en' => 'Hummus', 'ar' => 'حمص'], $dish->getTranslations('name'));
        $this->assertSame(['en' => 'Chickpeas, tahini', 'ar' => 'حمص حب'], $dish->getTranslations('ingredients'));
    }

    public function test_a_dish_is_created_in_english(): void
    {
        $restaurant = $this->owner();

        Livewire::test(CreateDish::class)
            ->fillForm(['restaurant_id' => $restaurant->id, 'name.en' => 'Fattoush', 'price' => 5, 'display_order' => 0])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['en' => 'Fattoush'], Dish::query()->where('restaurant_id', $restaurant->id)->sole()->getTranslations('name'));
    }

    public function test_a_category_of_a_french_menu_is_edited_in_french_and_keeps_the_english(): void
    {
        $restaurant = $this->owner(['main_locale' => 'fr']);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['en' => 'Mains', 'fr' => 'Plats']]);
        $this->actingAs($this->admin());

        Livewire::test(EditCategory::class, ['record' => $category->getRouteKey()])
            ->assertSee('Name (Français)')
            ->assertDontSee('Name (English)')
            ->fillForm(['name.fr' => 'Plats principaux'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['en' => 'Mains', 'fr' => 'Plats principaux'], $category->fresh()->getTranslations('name'));
    }

    public function test_a_dish_of_an_arabic_menu_needs_its_arabic_name(): void
    {
        $restaurant = $this->owner(['main_locale' => 'ar']);
        $this->actingAs($this->admin());

        Livewire::test(CreateDish::class)
            ->fillForm(['restaurant_id' => $restaurant->id, 'name.ar' => '', 'price' => 5, 'display_order' => 0])
            ->call('create')
            ->assertHasFormErrors(['name.ar' => 'required']);

        Livewire::test(CreateDish::class)
            ->fillForm(['restaurant_id' => $restaurant->id, 'name.ar' => 'فتوش', 'price' => 5, 'display_order' => 0])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertSame(['ar' => 'فتوش'], Dish::query()->latest('id')->first()->getTranslations('name'));
    }

    public function test_a_restaurant_made_arabic_from_the_admin_swaps_its_languages(): void
    {
        $restaurant = $this->owner(['main_locale' => 'en', 'second_locale' => 'ar', 'default_locale' => 'en', 'name' => ['en' => 'Olive', 'ar' => 'زيتون']]);
        $this->actingAs($this->admin());

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->getRouteKey()])
            ->fillForm(['main_locale' => 'ar'])
            ->assertSee('Name (العربية)')
            ->call('save')
            ->assertHasNoFormErrors();

        $restaurant = $restaurant->fresh();
        $this->assertSame('ar', $restaurant->main_locale);
        $this->assertSame('en', $restaurant->second_locale);
        $this->assertSame('ar', $restaurant->default_locale);
        $this->assertSame(['en' => 'Olive', 'ar' => 'زيتون'], $restaurant->getTranslations('name'));
    }

    public function test_a_menu_written_only_in_arabic_reads_by_name_in_the_admin_lists(): void
    {
        $restaurant = $this->owner([
            'main_locale' => 'ar',
            'second_locale' => 'fr',
            'name' => ['fr' => 'Chez Omaima', 'ar' => 'مطبخ أميمة'],
        ]);
        $category = Category::factory()->create(['restaurant_id' => $restaurant->id, 'name' => ['ar' => 'مشروبات']]);
        Dish::factory()->create(['restaurant_id' => $restaurant->id, 'category_id' => $category->id, 'name' => ['ar' => 'ليموناضة']]);

        // Neither English nor the admin's language is written: the lists
        // used to show "N/A". The restaurant reads in its main language.
        Livewire::test(ListRestaurants::class)->assertSee('مطبخ أميمة')->assertDontSee('Chez Omaima');
        Livewire::test(ListCategories::class)->assertSee('مشروبات')->assertSee('مطبخ أميمة');
        Livewire::test(ListDishes::class)->assertSee('ليموناضة')->assertSee('مشروبات');
    }

    public function test_a_package_edits_both_interface_languages(): void
    {
        $pro = Package::findBySlug('pro');

        Livewire::test(EditPackage::class, ['record' => $pro->id])
            ->assertSchemaStateSet(['name.en' => 'Pro', 'name.ar' => 'برو'])
            ->fillForm(['name.ar' => 'احترافي', 'description.en' => 'For busy menus.'])
            ->call('save')
            ->assertHasNoFormErrors();

        $pro = $pro->fresh();
        $this->assertSame(['en' => 'Pro', 'ar' => 'احترافي'], $pro->getTranslations('name'));
        $this->assertSame('For busy menus.', $pro->getTranslation('description', 'en'));
        $this->assertSame(150, $pro->featureValue(\App\Enums\Feature::DishLimit), 'The features are untouched.');
    }

    public function test_a_design_edits_both_languages_and_names_its_slug_from_the_english(): void
    {
        $template = Template::factory()->create(['name' => ['en' => 'Classic', 'ar' => 'كلاسيكي'], 'slug' => 'classic']);

        Livewire::test(EditTemplate::class, ['record' => $template->id])
            ->assertSchemaStateSet(['name.en' => 'Classic', 'name.ar' => 'كلاسيكي'])
            ->fillForm(['description.ar' => 'تصميم بسيط'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame(['en' => 'Classic', 'ar' => 'كلاسيكي'], $template->fresh()->getTranslations('name'));
        $this->assertSame('تصميم بسيط', $template->fresh()->getTranslation('description', 'ar'));

        Livewire::test(CreateTemplate::class)
            ->set('data.name.en', 'Midnight Blue')
            ->assertSchemaStateSet(['slug' => 'midnight-blue']);
    }
}
