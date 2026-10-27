<?php

namespace Tests\Feature\Api;

use App\Enums\BuildShape;
use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\FeatureGrant;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A dish's "build your own" saved from the dashboard's dish form: only for a
 * restaurant an admin switched it on for, a category's dishes or its own
 * list, part prices kept to the sizes it allows, and the list as sent
 * becoming the list.
 */
class DishBuildTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Category $pizzas;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages, Feature::BuildYourOwn);
        $this->shop = $this->owner(['second_locale' => 'ar']);
        $this->pizzas = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Pizzas']]);
    }

    private function pizza(string $name, string $price): Dish
    {
        return Dish::factory()->for($this->shop)->create(['category_id' => $this->pizzas->id, 'name' => ['en' => $name], 'price' => $price]);
    }

    /** @return array<string, mixed> */
    private function dish(array $builds): array
    {
        return ['name' => ['en' => 'Build your pizza'], 'price' => null, 'category_id' => $this->pizzas->id, 'builds' => $builds];
    }

    public function test_a_pizza_built_from_the_pizzas_category_with_a_half_priced_by_the_owner(): void
    {
        $margherita = $this->pizza('Margherita', '12.00');
        $this->pizza('Pepperoni', '16.00');

        $response = $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), $this->dish([[
            'name' => ['en' => 'Your pizza', 'ar' => 'بيتزاك'],
            'shape' => 'split',
            'parts' => [2, 1],
            'category_id' => $this->pizzas->id,
            // A half at $7, a third the build does not allow, and nothing for the other.
            'choices' => [['dish_id' => $margherita->id, 'part_prices' => ['2' => '7', '3' => '5']]],
        ]]))->assertCreated();

        $response->assertJsonPath('data.builds.0', [
            'id' => DishBuild::query()->sole()->id,
            'name' => ['en' => 'Your pizza', 'ar' => 'بيتزاك'],
            'shape' => 'split',
            'parts' => [1, 2],
            'category_id' => $this->pizzas->id,
            'dish_ids' => null,
            'choices' => [[
                'id' => $response->json('data.builds.0.choices.0.id'),
                'dish_id' => $margherita->id,
                'name' => null,
                'price' => null,
                'part_prices' => ['2' => '7.00'],
            ]],
        ]);
    }

    public function test_a_category_build_may_offer_only_the_dishes_ticked(): void
    {
        $margherita = $this->pizza('Margherita', '12.00');
        $pepperoni = $this->pizza('Pepperoni', '16.00');
        $this->pizza('Hawaiian', '14.00');
        $build = ['name' => ['en' => 'Your pizza'], 'shape' => 'split', 'parts' => [2], 'category_id' => $this->pizzas->id, 'choices' => []];

        $response = $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), $this->dish([[...$build, 'dish_ids' => [$pepperoni->id, $margherita->id, (string) $margherita->id]]]));

        // Kept as numbers, once each (a repeat is refused), in the order ticked.
        $response->assertUnprocessable()->assertJsonValidationErrors(['builds.0.dish_ids.1']);

        $response = $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), $this->dish([[...$build, 'dish_ids' => [$pepperoni->id, $margherita->id]]]))->assertCreated();
        $response->assertJsonPath('data.builds.0.dish_ids', [$pepperoni->id, $margherita->id]);
        $dish = Dish::query()->findOrFail($response->json('data.id'));

        // Sent again without the list: every dish of the category.
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => [[...$build, 'id' => $response->json('data.builds.0.id')]]])
            ->assertOk()
            ->assertJsonPath('data.builds.0.dish_ids', null);

        // An own list ticks nothing.
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => [[
            ...$build,
            'category_id' => null,
            'dish_ids' => [$pepperoni->id, $margherita->id],
            'choices' => [['name' => ['en' => 'Vanilla'], 'price' => 2], ['name' => ['en' => 'Mango'], 'price' => 2]],
        ]]])->assertOk()->assertJsonPath('data.builds.0.dish_ids', null);
    }

    public function test_a_ticked_dish_must_be_of_the_builds_category_and_not_the_dish_itself(): void
    {
        $elsewhere = Dish::factory()->for($this->shop)->create(['category_id' => Category::factory()->for($this->shop)->create()->id]);
        $theirs = Dish::factory()->create();
        $dish = $this->pizza('Build your pizza', '0');
        $margherita = $this->pizza('Margherita', '12.00');
        $build = ['name' => ['en' => 'Your pizza'], 'shape' => 'split', 'parts' => [2], 'category_id' => $this->pizzas->id, 'choices' => []];

        foreach ([$elsewhere->id, $theirs->id, $dish->id] as $id) {
            $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => [[...$build, 'dish_ids' => [$margherita->id, $id]]]])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['builds.0.dish_ids.1']);
        }
    }

    public function test_a_builds_dish_has_an_icon_and_no_category(): void
    {
        $this->pizza('Margherita', '12.00');
        $build = ['name' => ['en' => 'Your pizza'], 'shape' => 'split', 'parts' => [2], 'category_id' => $this->pizzas->id, 'choices' => []];

        $response = $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), [
            ...$this->dish([$build]),
            'category_id' => null,
            'icon' => 'pizza',
        ])->assertCreated();

        $response->assertJsonPath('data.category_id', null)->assertJsonPath('data.icon', 'pizza');
        $dish = Dish::query()->findOrFail($response->json('data.id'));

        // Edited, it may leave a category it was filed under before.
        $dish->update(['category_id' => $this->pizzas->id]);
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['category_id' => null, 'icon' => 'flame', 'builds' => [$build]])
            ->assertOk()
            ->assertJsonPath('data.category_id', null)
            ->assertJsonPath('data.icon', 'flame');
        // Its saved builds count when the edit sends none.
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['category_id' => null])->assertOk();
    }

    public function test_a_dish_without_a_build_still_needs_a_category_and_a_known_icon(): void
    {
        $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), [...$this->dish([]), 'price' => '5', 'category_id' => null, 'icon' => 'rocket'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id', 'icon']);

        $dish = $this->pizza('Margherita', '12.00');
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['category_id' => null])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_a_restaurant_has_three_dishes_guests_build_unless_an_admin_sets_more(): void
    {
        $this->pizza('Margherita', '12.00');
        $this->pizza('Pepperoni', '16.00');
        $build = ['name' => ['en' => 'Your pizza'], 'shape' => 'split', 'parts' => [2], 'category_id' => $this->pizzas->id, 'choices' => []];
        $make = fn () => $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), [...$this->dish([$build]), 'category_id' => null]);

        foreach (range(1, 3) as $made) {
            $make()->assertCreated();
        }

        // A fourth is refused, saying the limit; nothing is made.
        $make()->assertUnprocessable()->assertJsonValidationErrors(['builds' => 'up to 3 dishes guests build']);
        $this->assertSame(3, $this->shop->dishes()->whereHas('builds')->count());

        // A dish without a build is still a dish like any other.
        $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), [...$this->dish([]), 'price' => '5'])->assertCreated();

        // One already built can be changed at the limit; a plain one cannot become one.
        $built = $this->shop->dishes()->whereHas('builds')->with('builds')->firstOrFail();
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $built), ['builds' => [[...$build, 'id' => $built->builds->first()->id, 'parts' => [1, 2]]]])->assertOk();
        $plain = $this->pizza('Hawaiian', '14.00');
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $plain), ['builds' => [$build]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['builds']);

        // An admin gives this restaurant 4.
        FeatureGrant::factory()->for($this->shop)->forFeature(Feature::BuildLimit, 4)->create(['mode' => GrantMode::Set]);
        $make()->assertCreated();
        $this->assertSame(4, $this->shop->dishes()->whereHas('builds')->count());
    }

    public function test_scoops_from_the_owners_own_list(): void
    {
        $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), [
            ...$this->dish([[
                'name' => ['en' => 'Your scoops'],
                'shape' => 'pieces',
                'parts' => [3],
                'category_id' => null,
                'choices' => [
                    ['name' => ['en' => 'Vanilla'], 'price' => '0', 'part_prices' => ['2' => '9']],
                    ['name' => ['en' => 'Pistachio', 'ar' => 'فستق'], 'price' => '1.5'],
                ],
            ]]),
            'price' => '6.00',
        ])->assertCreated()
            ->assertJsonPath('data.builds.0.shape', 'pieces')
            ->assertJsonPath('data.builds.0.choices.0.name', ['en' => 'Vanilla', 'ar' => null])
            ->assertJsonPath('data.builds.0.choices.0.price', '0.00')
            // Pieces have no part sizes.
            ->assertJsonPath('data.builds.0.choices.0.part_prices', [])
            ->assertJsonPath('data.builds.0.choices.1.price', '1.50');
    }

    public function test_a_saved_build_is_updated_and_one_left_out_goes(): void
    {
        $dish = Dish::factory()->for($this->shop)->create(['category_id' => $this->pizzas->id, 'price' => '5.00']);
        $kept = DishBuild::factory()->for($dish)->withChoices(['Vanilla' => 0, 'Chocolate' => 1])->create(['name' => ['en' => 'Scoops', 'ar' => 'كرات']]);
        $gone = DishBuild::factory()->for($dish)->withChoices()->create();
        $vanilla = $kept->choices()->first();

        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => [[
            'id' => $kept->id,
            'name' => ['en' => 'Your scoops'],
            'shape' => 'pieces',
            'parts' => [2, 3],
            'category_id' => null,
            'choices' => [
                ['id' => $vanilla->id, 'name' => ['en' => 'Vanilla bean'], 'price' => '0.50'],
                ['name' => ['en' => 'Mango'], 'price' => '1'],
            ],
        ]]])->assertOk();

        $this->assertModelMissing($gone);
        $kept->refresh();
        $this->assertSame([2, 3], $kept->partCounts());
        // Arabic, a language the form did not send, stays.
        $this->assertSame(['en' => 'Your scoops', 'ar' => 'كرات'], $kept->getTranslations('name'));
        $this->assertSame(['Vanilla bean', 'Mango'], $kept->choices->map(fn ($choice) => $choice->getTranslation('name', 'en'))->all());
        $this->assertSame($vanilla->id, $kept->choices->first()->id);
    }

    public function test_builds_saved_on_their_own_leave_the_rest_of_the_dish_as_it_is(): void
    {
        // The dashboard's Build your own page sends only the builds.
        $this->pizza('Margherita', '12.00');
        $this->pizza('Pepperoni', '16.00');
        $dish = Dish::factory()->for($this->shop)->create([
            'category_id' => $this->pizzas->id,
            'name' => ['en' => 'Build your pizza', 'ar' => 'ابنِ البيتزا'],
            'ingredients' => ['en' => 'Two halves'],
            'price' => null,
            'is_available' => false,
        ]);

        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => [[
            'name' => ['en' => 'Your pizza'],
            'shape' => 'split',
            'parts' => [1, 2],
            'category_id' => $this->pizzas->id,
            'choices' => [],
        ]]])->assertOk()->assertJsonPath('data.builds.0.name.en', 'Your pizza');

        $dish->refresh();
        $this->assertSame(['en' => 'Build your pizza', 'ar' => 'ابنِ البيتزا'], $dish->getTranslations('name'));
        $this->assertSame('Two halves', $dish->getTranslation('ingredients', 'en'));
        $this->assertNull($dish->price);
        $this->assertFalse($dish->is_available);
        $this->assertSame(1, $dish->builds()->count());

        // And taking them all off again.
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => []])->assertOk();
        $this->assertSame(0, $dish->builds()->count());
        $this->assertSame('Build your pizza', $dish->refresh()->getTranslation('name', 'en'));
    }

    public function test_an_edit_that_sends_no_builds_leaves_them(): void
    {
        $dish = Dish::factory()->for($this->shop)->create(['category_id' => $this->pizzas->id, 'price' => null]);
        DishBuild::factory()->for($dish)->withChoices()->create();

        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['name' => ['en' => 'Renamed']])->assertOk();

        $this->assertSame(1, $dish->builds()->count());
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function refused(): array
    {
        $own = ['name' => ['en' => 'Scoops'], 'shape' => 'pieces', 'parts' => [2], 'category_id' => null];

        return [
            'no name' => [[...$own, 'name' => ['en' => ''], 'choices' => [['name' => ['en' => 'A'], 'price' => 1], ['name' => ['en' => 'B'], 'price' => 1]]], 'builds.0.name.en'],
            'no shape' => [[...$own, 'shape' => 'round', 'choices' => []], 'builds.0.shape'],
            'no part count' => [[...$own, 'parts' => [], 'choices' => []], 'builds.0.parts'],
            'too many parts' => [[...$own, 'parts' => [9], 'choices' => []], 'builds.0.parts.0'],
            'one choice' => [[...$own, 'choices' => [['name' => ['en' => 'A'], 'price' => 1]]], 'builds.0.choices'],
            'a choice without a name' => [[...$own, 'choices' => [['name' => ['en' => 'A'], 'price' => 1], ['name' => ['en' => ''], 'price' => 1]]], 'builds.0.choices.1.name.en'],
            'a choice without a price' => [[...$own, 'choices' => [['name' => ['en' => 'A'], 'price' => 1], ['name' => ['en' => 'B']]]], 'builds.0.choices.1.price'],
            'a negative part price' => [[...$own, 'choices' => [['name' => ['en' => 'A'], 'price' => 1, 'part_prices' => ['2' => -1]], ['name' => ['en' => 'B'], 'price' => 1]]], 'builds.0.choices.0.part_prices.2'],
        ];
    }

    /** @param  array<string, mixed>  $build */
    #[\PHPUnit\Framework\Attributes\DataProvider('refused')]
    public function test_a_build_that_cannot_be_filled_is_refused(array $build, string $field): void
    {
        $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), [...$this->dish([$build]), 'price' => '5'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$field]);

        $this->assertSame(0, DishBuild::query()->count());
    }

    public function test_a_categorys_choice_must_be_a_dish_of_that_category_and_not_the_dish_itself(): void
    {
        $elsewhere = Dish::factory()->for($this->shop)->create(['category_id' => Category::factory()->for($this->shop)->create()->id]);
        $theirs = Dish::factory()->create();
        $dish = $this->pizza('Build your pizza', '0');
        $build = ['name' => ['en' => 'Your pizza'], 'shape' => 'split', 'parts' => [2], 'category_id' => $this->pizzas->id];

        foreach ([$elsewhere->id, $theirs->id, $dish->id] as $id) {
            $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => [[...$build, 'choices' => [['dish_id' => $id, 'part_prices' => ['2' => 5]]]]]])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['builds.0.choices.0.dish_id']);
        }

        // Another restaurant's category is no category of this one's.
        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => [[...$build, 'category_id' => Category::factory()->create()->id, 'choices' => []]]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['builds.0.category_id']);
    }

    public function test_builds_price_a_dish_with_no_price_of_its_own_and_its_add_ons(): void
    {
        $this->pizza('Margherita', '12.00');
        $this->defaultPackageIncludes(Feature::Addons);

        $this->actingAs($this->shop->user)->postJson(route('api.dishes.store'), [
            ...$this->dish([['name' => ['en' => 'Your pizza'], 'shape' => 'split', 'parts' => [2], 'category_id' => $this->pizzas->id, 'choices' => []]]),
            'addons' => [['name' => ['en' => 'Extra cheese'], 'price' => '1']],
        ])->assertCreated();
    }

    public function test_without_the_feature_builds_are_left_alone(): void
    {
        $this->defaultPackageSets(Feature::BuildYourOwn, 0);
        $dish = Dish::factory()->for($this->shop)->create(['category_id' => $this->pizzas->id, 'price' => '9']);
        $saved = DishBuild::factory()->for($dish)->withChoices()->create();

        $this->actingAs($this->shop->user)->patchJson(route('api.dishes.update', $dish), ['builds' => []])->assertOk();

        $this->assertModelExists($saved);
        $this->assertSame(BuildShape::Split, $saved->fresh()->shape);
    }
}
