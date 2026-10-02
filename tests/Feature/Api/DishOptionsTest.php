<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishVariantOption;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A dish's variants and add-ons as the dashboard's dish form writes them.
 */
class DishOptionsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $restaurant;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Variants, Feature::Addons, Feature::MultipleLanguages);
        $this->restaurant = $this->owner(['second_locale' => 'ar']);
        $this->category = Category::factory()->create(['restaurant_id' => $this->restaurant->id]);
    }

    /** @return array<string, mixed> */
    private function form(array $overrides = []): array
    {
        return [
            'name' => ['en' => 'Burger', 'ar' => 'برغر'],
            'price' => 8,
            'category_id' => $this->category->id,
            'variants' => [[
                'name' => ['en' => 'Size', 'ar' => 'الحجم'],
                'options' => [
                    ['name' => ['en' => 'Small', 'ar' => 'صغير'], 'price' => 0],
                    ['name' => ['en' => 'Large', 'ar' => 'كبير'], 'price' => 3],
                ],
            ]],
            'addons' => [
                ['name' => ['en' => 'Extra cheese', 'ar' => 'جبنة إضافية'], 'price' => 1],
            ],
            ...$overrides,
        ];
    }

    private function store(array $form): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->restaurant->user()->firstOrFail())->postJson(route('api.dishes.store'), $form);
    }

    private function update(Dish $dish, array $form): \Illuminate\Testing\TestResponse
    {
        return $this->actingAs($this->restaurant->user()->firstOrFail())->patchJson(route('api.dishes.update', $dish), $form);
    }

    public function test_a_new_dish_is_saved_with_its_variants_and_addons(): void
    {
        $response = $this->store($this->form())->assertCreated()
            ->assertJsonPath('data.variants.0.name', ['en' => 'Size', 'ar' => 'الحجم'])
            ->assertJsonPath('data.variants.0.options.1.name.en', 'Large')
            ->assertJsonPath('data.variants.0.options.1.price', '3.00')
            ->assertJsonPath('data.addons.0.name.ar', 'جبنة إضافية')
            ->assertJsonPath('data.addons.0.price', '1.00');

        $dish = Dish::query()->findOrFail($response->json('data.id'));
        $this->assertSame([1], $dish->variants->pluck('display_order')->all());
        $this->assertSame([1, 2], $dish->variants->first()->options->pluck('display_order')->all());
    }

    public function test_an_edit_keeps_rows_by_id_reorders_them_and_drops_the_rest(): void
    {
        $dish = Dish::query()->findOrFail($this->store($this->form())->json('data.id'));
        $variant = $dish->variants->first();
        [$small, $large] = $variant->options->all();
        $variant->setTranslation('name', 'fr', 'Taille')->save();

        $this->update($dish, [
            'variants' => [[
                'id' => $variant->id,
                'name' => ['en' => 'Portion', 'ar' => 'الحصة'],
                'options' => [
                    ['id' => $large->id, 'name' => ['en' => 'Big', 'ar' => 'كبير'], 'price' => 4],
                    ['name' => ['en' => 'Medium', 'ar' => ''], 'price' => 1.5],
                    ['id' => $small->id, 'name' => ['en' => 'Small', 'ar' => 'صغير'], 'price' => 0],
                ],
            ]],
            'addons' => [],
        ])->assertOk()->assertJsonPath('data.addons', []);

        $variant->refresh();
        $this->assertSame('Portion', $variant->getTranslation('name', 'en'));
        // A language the dashboard does not show is never written over.
        $this->assertSame('Taille', $variant->getTranslation('name', 'fr'));
        $this->assertSame(['Big', 'Medium', 'Small'], $variant->options->map(fn ($o) => $o->getTranslation('name', 'en'))->all());
        $this->assertSame($large->id, $variant->options->first()->id);
        $this->assertSame('', $variant->options[1]->getTranslation('name', 'ar', false));
        $this->assertSame(0, $dish->addons()->count());
    }

    public function test_an_edit_that_leaves_the_lists_out_leaves_them_alone(): void
    {
        $dish = Dish::query()->findOrFail($this->store($this->form())->json('data.id'));

        $this->update($dish, ['price' => 9])->assertOk()->assertJsonCount(1, 'data.variants')->assertJsonCount(1, 'data.addons');
    }

    public function test_an_id_from_another_dish_makes_a_new_row_here(): void
    {
        $other = Dish::factory()->withVariants()->create(['restaurant_id' => $this->restaurant->id]);
        $foreign = $other->variants->first();

        $form = $this->form();
        $form['variants'][0]['id'] = $foreign->id;
        $form['variants'][0]['options'][0]['id'] = $foreign->options->first()->id;
        $id = $this->store($form)->assertCreated()->json('data.id');

        $this->assertNotSame($foreign->id, Dish::query()->findOrFail($id)->variants->first()->id);
        $this->assertSame('Size', $foreign->fresh()->getTranslation('name', 'en'));
        $this->assertSame(3, $foreign->options()->count());
    }

    public function test_deleting_a_dish_takes_its_choices_with_it(): void
    {
        $dish = Dish::query()->findOrFail($this->store($this->form())->json('data.id'));

        $this->actingAs($this->restaurant->user)->deleteJson(route('api.dishes.destroy', $dish))->assertNoContent();

        $this->assertSame(0, DishVariantOption::query()->count());
        $this->assertDatabaseCount('dish_variants', 0);
        $this->assertDatabaseCount('dish_addons', 0);
    }

    public function test_what_is_wrong_is_said_plainly(): void
    {
        $this->store($this->form([
            'variants' => [
                ['name' => ['en' => ''], 'options' => [['name' => ['en' => 'Only one'], 'price' => 0]]],
                ['name' => ['en' => 'Spice'], 'options' => [['name' => ['en' => ''], 'price' => -1], ['name' => ['en' => 'Hot'], 'price' => 0]]],
            ],
            'addons' => [['name' => ['en' => ''], 'price' => 100000]],
        ]))->assertUnprocessable()
            ->assertJsonPath('errors', fn (array $errors): bool => $errors['variants.0.name.en'] === ['Give every variant a name in English.']
                && $errors['variants.0.options'] === ['A variant needs at least 2 options.']
                && $errors['variants.1.options.0.name.en'] === ['Give every option a name in English.']
                && isset($errors['variants.1.options.0.price'])
                && $errors['addons.0.name.en'] === ['Give every add-on a name in English.']
                && isset($errors['addons.0.price']));
    }

    public function test_the_messages_come_in_arabic_too(): void
    {
        $errors = $this->actingAs($this->restaurant->user)
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('api.dishes.store'), $this->form(['price' => null, 'addons' => [['name' => ['en' => ''], 'price' => 1]]]))
            ->assertUnprocessable()
            ->json('errors');

        $this->assertSame(['اكتب اسم كل إضافة بالإنجليزية.'], $errors['addons.0.name.en']);
        $this->assertSame(['أضف سعرًا للطبق قبل إضافة الخيارات أو الإضافات.'], $errors['price']);
    }

    public function test_choices_need_the_dish_to_have_a_price(): void
    {
        $this->store($this->form(['price' => null]))
            ->assertUnprocessable()
            ->assertJsonPath('errors.price.0', 'Give the dish a price before adding variants or add-ons.');

        // An edit that sends no price is checked against the saved one.
        $dish = Dish::factory()->create(['restaurant_id' => $this->restaurant->id, 'category_id' => $this->category->id, 'price' => null]);
        $this->update($dish, ['addons' => [['name' => ['en' => 'Cheese'], 'price' => 1]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);

        // Empty lists ask for nothing.
        $this->store($this->form(['price' => null, 'variants' => [], 'addons' => []]))->assertCreated();
    }

    public function test_a_dish_holds_at_most_the_configured_number_of_each(): void
    {
        $option = ['name' => ['en' => 'x'], 'price' => 0];
        $variant = ['name' => ['en' => 'v'], 'options' => [$option, $option]];

        $this->store($this->form([
            'variants' => array_fill(0, 6, $variant),
            'addons' => array_fill(0, 21, $option),
        ]))->assertUnprocessable()->assertJsonValidationErrors(['variants', 'addons']);

        $this->store($this->form(['variants' => [['name' => ['en' => 'v'], 'options' => array_fill(0, 11, $option)]]]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['variants.0.options']);
    }

    public function test_a_switched_off_list_is_left_as_it_is(): void
    {
        $dish = Dish::query()->findOrFail($this->store($this->form())->json('data.id'));
        $this->restaurant->update(['switched_off' => ['variants']]);

        // Sent anyway (a stale page): neither checked nor saved.
        $this->update($dish, ['variants' => [['name' => ['en' => '']]], 'addons' => []])
            ->assertOk()
            ->assertJsonCount(1, 'data.variants')
            ->assertJsonCount(0, 'data.addons');

        $this->assertSame('Size', $dish->variants()->first()->getTranslation('name', 'en'));
    }

    public function test_a_package_without_them_saves_neither(): void
    {
        $this->defaultPackageSets(Feature::Variants, 0);
        $this->defaultPackageSets(Feature::Addons, 0);

        $id = $this->store($this->form(['price' => null]))->assertCreated()->json('data.id');

        $this->assertSame(0, Dish::query()->findOrFail($id)->variants()->count());
        $this->assertSame(0, Dish::query()->findOrFail($id)->addons()->count());
    }
}
