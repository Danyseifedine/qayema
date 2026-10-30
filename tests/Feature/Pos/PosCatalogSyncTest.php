<?php

namespace Tests\Feature\Pos;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Pos\PosCatalogSync;
use App\Services\Pos\PosException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The POS catalog applied to a menu (PosCatalogSync), from the snapshot the
 * POS sends (tests/Fixtures/pos-contract/catalog.json, kept in step with the
 * POS's own copy): what each POS field becomes, what is kept, and what is
 * listed as a problem instead.
 */
class PosCatalogSyncTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    /** What the POS answers for a photo. */
    private mixed $photo;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages, Feature::Variants, Feature::Addons, Feature::PosConnection);
        $this->shop = $this->owner(['second_locale' => 'ar', 'currency' => 'USD']);
        $this->photo = Http::response(UploadedFile::fake()->image('hummus.jpg', 300, 300)->get(), 200, ['Content-Type' => 'image/jpeg']);
        Http::fake(fn () => $this->photo);
    }

    /** @return array<string, mixed> */
    private function fixture(): array
    {
        return json_decode((string) file_get_contents(base_path('tests/Fixtures/pos-contract/catalog.json')), true)['data'];
    }

    /** @return array{changed: bool, problems: list<array{type: string, message: string}>} */
    private function apply(array $snapshot): array
    {
        return app(PosCatalogSync::class)->apply($this->shop->fresh(), $snapshot);
    }

    public function test_the_contract_catalog_becomes_categories_dishes_and_choices(): void
    {
        $result = $this->apply($this->fixture());

        $this->assertTrue($result['changed']);
        $this->assertSame([], $result['problems']);

        $category = Category::query()->where('restaurant_id', $this->shop->id)->sole();
        $this->assertSame('12', $category->pos_id);
        $this->assertSame(['en' => 'Mezze', 'ar' => 'مقبلات'], $category->getTranslations('name'));
        $this->assertSame(['en' => 'Small plates'], $category->getTranslations('description'));

        $dish = Dish::query()->where('restaurant_id', $this->shop->id)->with(['variants.options', 'addonGroups'])->sole();
        $this->assertSame('301', $dish->pos_id);
        $this->assertSame($category->id, $dish->category_id);
        $this->assertSame(['en' => 'Hummus', 'ar' => 'حمص'], $dish->getTranslations('name'));
        $this->assertSame(['en' => 'Chickpeas, Tahini, Lemon', 'ar' => 'حمص حب، طحينة، ليمون'], $dish->getTranslations('ingredients'));
        $this->assertSame('5.50', (string) $dish->price);
        $this->assertNull($dish->currency);
        $this->assertTrue($dish->is_available);
        $this->assertSame('7-1730200000', $dish->pos_image_version);
        $this->assertNotNull($dish->getFirstMedia('image'));

        // A required group of one option stays a group the guest takes as is.
        $this->assertCount(0, $dish->variants);
        $group = $dish->addonGroups->sole();
        $this->assertSame([1, 1], [$group->min_select, $group->max_select]);
        $this->assertSame('2.00', (string) $group->addons()->sole()->price);
    }

    public function test_applying_the_same_catalog_again_changes_nothing(): void
    {
        $this->apply($this->fixture());

        $result = $this->apply($this->fixture());

        $this->assertFalse($result['changed']);
        Http::assertSentCount(1);
    }

    public function test_a_required_pick_one_group_is_a_variant_and_any_other_an_add_on_group_with_its_limits(): void
    {
        $snapshot = $this->fixture();
        $snapshot['products'][0]['image'] = null;
        $snapshot['products'][0]['modifier_groups'] = [
            ['id' => '4', 'name' => ['en' => 'Size', 'ar' => null], 'selection' => 'single', 'required' => true, 'min' => 1, 'max' => 1, 'options' => [
                ['id' => '9', 'name' => ['en' => 'Small', 'ar' => null], 'price_delta' => '0.00'],
                ['id' => '10', 'name' => ['en' => 'Large', 'ar' => null], 'price_delta' => '2.00'],
            ]],
            ['id' => '5', 'name' => ['en' => 'Sauces', 'ar' => null], 'selection' => 'multiple', 'required' => false, 'min' => 0, 'max' => 2, 'options' => [
                ['id' => '11', 'name' => ['en' => 'Garlic', 'ar' => null], 'price_delta' => '0.50'],
                ['id' => '12', 'name' => ['en' => 'Chili', 'ar' => null], 'price_delta' => '0.75'],
                ['id' => '13', 'name' => ['en' => 'Tahini', 'ar' => null], 'price_delta' => '0.25'],
            ]],
        ];

        $this->apply($snapshot);

        $dish = Dish::query()->with(['variants.options', 'addonGroups.addons'])->sole();
        $this->assertSame(['Small', 'Large'], $dish->variants->sole()->options->map(fn ($option) => $option->getTranslation('name', 'en'))->all());
        $this->assertSame(['0.00', '2.00'], $dish->variants->sole()->options->map(fn ($option) => (string) $option->price)->all());
        $sauces = $dish->addonGroups->sole();
        $this->assertSame([0, 2], [$sauces->min_select, $sauces->max_select]);
        $this->assertSame(['Garlic', 'Chili', 'Tahini'], $sauces->addons->map(fn ($addon) => $addon->getTranslation('name', 'en'))->all());
    }

    public function test_a_choice_the_owner_added_before_the_link_is_replaced_by_the_pos_ones(): void
    {
        $snapshot = $this->fixture();
        $category = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Mezze']]);
        $dish = Dish::factory()->for($this->shop)->create(['category_id' => $category->id, 'name' => ['en' => 'hummus'], 'icon' => 'bowl']);
        $dish->addons()->create(['name' => ['en' => 'Bread'], 'price' => 1]);

        $this->apply($snapshot);

        $dish->refresh();
        $this->assertSame('301', $dish->pos_id, 'The owner\'s dish of the same name is taken over on the first sync.');
        $this->assertSame('bowl', $dish->icon);
        $this->assertSame($category->id, $dish->category_id);
        $this->assertSame(['2.00'], $dish->addons()->pluck('price')->map(fn ($price) => (string) $price)->all());
        $this->assertSame(1, Dish::query()->count());
    }

    public function test_what_leaves_the_pos_is_hidden_and_comes_back(): void
    {
        $this->apply($this->fixture());
        $empty = [...$this->fixture(), 'categories' => [], 'products' => []];

        $this->apply($empty);

        $dish = Dish::query()->sole();
        $this->assertFalse($dish->is_available);
        $this->assertNotNull($dish->pos_removed_at);
        $this->assertNotNull(Category::query()->sole()->pos_removed_at);

        $this->apply($this->fixture());

        $this->assertTrue($dish->fresh()->is_available);
        $this->assertNull($dish->fresh()->pos_removed_at);
        $this->assertNull(Category::query()->sole()->pos_removed_at);
    }

    public function test_a_language_the_pos_leaves_empty_keeps_what_the_menu_had(): void
    {
        $this->apply($this->fixture());
        $dish = Dish::query()->sole();
        $dish->setTranslation('name', 'ar', 'حمص بيروتي')->save();
        $snapshot = $this->fixture();
        $snapshot['products'][0]['name']['ar'] = null;

        $this->apply($snapshot);

        $this->assertSame('حمص بيروتي', $dish->fresh()->getTranslation('name', 'ar'));
    }

    public function test_without_a_recipe_the_description_becomes_the_ingredients_line(): void
    {
        $snapshot = $this->fixture();
        $snapshot['products'][0]['ingredients'] = null;
        $snapshot['products'][0]['description'] = ['en' => 'Chickpeas, tahini and lemon', 'ar' => null];

        $this->apply($snapshot);

        $this->assertSame(['en' => 'Chickpeas, tahini and lemon'], Dish::query()->sole()->getTranslations('ingredients'));
    }

    public function test_a_dish_in_the_second_currency_takes_its_option_prices_at_the_rate(): void
    {
        $this->shop->update(['second_currency' => 'LBP', 'exchange_rate' => '0.00001']);
        $snapshot = $this->fixture();
        $snapshot['products'][0]['price'] = '450000.00';
        $snapshot['products'][0]['currency'] = 'LBP';

        $this->apply($snapshot);

        $dish = Dish::query()->sole();
        $this->assertSame('LBP', $dish->currency);
        $this->assertSame('200000.00', (string) $dish->addons()->sole()->price);
    }

    public function test_a_product_in_a_currency_the_menu_does_not_use_or_with_no_category_is_listed_not_added(): void
    {
        $snapshot = $this->fixture();
        $snapshot['products'][] = [...$snapshot['products'][0], 'id' => '302', 'name' => ['en' => 'Falafel', 'ar' => null], 'currency' => 'EUR', 'image' => null];
        $snapshot['products'][] = [...$snapshot['products'][0], 'id' => '303', 'name' => ['en' => 'Loose', 'ar' => null], 'category_id' => null, 'image' => null];

        $result = $this->apply($snapshot);

        $this->assertSame(['currency', 'category'], array_column($result['problems'], 'type'));
        $this->assertSame(['301'], Dish::query()->pluck('pos_id')->all());
    }

    public function test_the_package_limits_still_count_and_what_does_not_fit_is_listed(): void
    {
        $this->defaultPackageSets(Feature::DishLimit, 1);
        $snapshot = $this->fixture();
        $snapshot['products'][] = [...$snapshot['products'][0], 'id' => '302', 'name' => ['en' => 'Falafel', 'ar' => null], 'image' => null];

        $result = $this->apply($snapshot);

        $this->assertSame(['limit'], array_column($result['problems'], 'type'));
        $this->assertSame(1, Dish::query()->count());
    }

    public function test_a_photo_that_will_not_download_is_listed_and_tried_again_next_time(): void
    {
        $this->photo = Http::response('not an image', 200, ['Content-Type' => 'text/html']);

        $result = $this->apply($this->fixture());

        $this->assertSame(['photo'], array_column($result['problems'], 'type'));
        $this->assertNull(Dish::query()->sole()->pos_image_version);
    }

    public function test_a_catalog_in_another_main_currency_is_refused_whole(): void
    {
        $this->expectException(PosException::class);

        $this->apply([...$this->fixture(), 'currency' => 'EUR']);
    }
}
