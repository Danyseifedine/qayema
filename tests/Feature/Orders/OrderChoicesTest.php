<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A guest's variants and add-ons on an order: priced by the server, required
 * where the dish asks for them, and kept on the line as they were.
 */
class OrderChoicesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $burger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::Variants, Feature::Addons, Feature::MultipleLanguages);
        $this->shop = $this->published(['slug' => 'olive', 'currency' => 'USD', 'country_code' => 'LB', 'phone' => '70123456']);
        $this->burger = Dish::factory()
            ->withVariants(['Size' => ['Small' => 0, 'Large' => 3], 'Spice level' => ['Mild' => 0, 'Hot' => 0]])
            ->withAddons(['Extra cheese' => 1, 'Bacon' => 2.5])
            ->create([
                'restaurant_id' => $this->shop->id,
                'category_id' => Category::factory()->create(['restaurant_id' => $this->shop->id])->id,
                'name' => ['en' => 'Burger'],
                'price' => '8.00',
            ]);
    }

    /** @return array<string, int> option and add-on ids by English name */
    private function ids(): array
    {
        $ids = [];
        foreach ($this->burger->variants()->with('options')->get() as $variant) {
            foreach ($variant->options as $option) {
                $ids[$option->getTranslation('name', 'en')] = $option->id;
            }
        }
        foreach ($this->burger->addons as $addon) {
            $ids[$addon->getTranslation('name', 'en')] = $addon->id;
        }

        return $ids;
    }

    /** @param  array<int, array<string, mixed>>  $items */
    private function order(array $items, array $extra = []): TestResponse
    {
        return $this->postJson(route('public.order', $this->shop->slug), ['items' => $items, ...$extra]);
    }

    public function test_choices_are_priced_by_the_server_and_kept_on_the_line(): void
    {
        $id = $this->ids();

        $this->order([[
            'dish_id' => $this->burger->id,
            'quantity' => 2,
            'options' => [$id['Hot'], $id['Large']],
            'addons' => [$id['Bacon'], $id['Extra cheese']],
            // A price from the page is never read.
            'unit_price' => '0.01',
        ]])->assertCreated()->assertJsonPath('data.total', '29.00');

        $line = $this->shop->orders()->first()->items()->first();
        $this->assertSame('14.50', (string) $line->unit_price);
        $this->assertSame('29.00', (string) $line->line_total);
        // The dish's order, not the order the ids came in.
        $this->assertSame([
            'variants' => [
                ['name' => 'Size', 'choice' => 'Large', 'price' => '3.00'],
                ['name' => 'Spice level', 'choice' => 'Hot', 'price' => '0.00'],
            ],
            'addons' => [
                ['name' => 'Extra cheese', 'price' => '1.00'],
                ['name' => 'Bacon', 'price' => '2.50'],
            ],
        ], $line->options);
        $this->assertSame(['Size: Large', 'Spice level: Hot', '+ Extra cheese', '+ Bacon'], $line->choices());
    }

    public function test_the_same_dish_with_other_choices_is_a_line_of_its_own(): void
    {
        $id = $this->ids();

        $this->order([
            ['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$id['Small'], $id['Mild']]],
            ['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$id['Large'], $id['Mild']]],
            // The same as the first, ids in another order: merged into it.
            ['dish_id' => $this->burger->id, 'quantity' => 2, 'options' => [$id['Mild'], $id['Small']]],
        ])->assertCreated()->assertJsonPath('data.total', '35.00');

        $lines = $this->shop->orders()->first()->items;
        $this->assertSame([3, 1], $lines->pluck('quantity')->all());
        $this->assertSame(['8.00', '11.00'], $lines->map(fn ($line): string => (string) $line->unit_price)->all());
    }

    public function test_every_variant_needs_one_option(): void
    {
        $id = $this->ids();

        $this->order([['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$id['Large']]]])
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'Choose a Spice level for Burger.');

        $this->order([['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$id['Small'], $id['Large'], $id['Hot']]]])
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'Choose only one Size for Burger.');

        $this->assertSame(0, $this->shop->orders()->count());
    }

    public function test_the_message_asking_for_a_choice_comes_in_the_guests_language(): void
    {
        $this->shop->update(['second_locale' => 'ar']);
        $this->burger->setTranslation('name', 'ar', 'برغر')->save();
        $this->burger->variants()->first()->setTranslation('name', 'ar', 'الحجم')->save();
        $id = $this->ids();

        $this->order([['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$id['Hot']]]], ['locale' => 'ar'])
            ->assertUnprocessable()
            ->assertJsonPath('errors.items.0', 'اختر الحجم لطبق برغر.');
    }

    public function test_choices_from_another_dish_are_ignored(): void
    {
        $id = $this->ids();
        $other = Dish::factory()->withVariants(['Size' => ['S' => 50, 'L' => 90]])->withAddons(['Gold leaf' => 99])->create([
            'restaurant_id' => $this->shop->id,
            'price' => '1.00',
        ]);
        $foreign = $other->variants()->first()->options()->pluck('id')->all();

        $this->order([[
            'dish_id' => $this->burger->id,
            'quantity' => 1,
            'options' => [$id['Small'], $id['Mild'], ...$foreign],
            'addons' => [$other->addons()->first()->id],
        ]])->assertCreated()->assertJsonPath('data.total', '8.00');

        $this->assertSame(['Size: Small', 'Spice level: Mild'], $this->shop->orders()->first()->items()->first()->choices());
    }

    public function test_a_variant_with_a_single_option_is_not_asked_for(): void
    {
        $plain = Dish::factory()->withVariants(['Bread' => ['Saj' => 0.5]])->create(['restaurant_id' => $this->shop->id, 'price' => '4.00']);

        $this->order([['dish_id' => $plain->id, 'quantity' => 1]])->assertCreated()->assertJsonPath('data.total', '4.00');
        $this->assertNull($this->shop->orders()->first()->items()->first()->options);
    }

    public function test_switched_off_choices_are_not_asked_for_nor_charged(): void
    {
        $this->shop->update(['switched_off' => ['variants', 'addons']]);
        $id = $this->ids();

        $this->order([['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$id['Large']], 'addons' => [$id['Bacon']]]])
            ->assertCreated()
            ->assertJsonPath('data.total', '8.00');

        $line = $this->shop->orders()->first()->items()->first();
        $this->assertNull($line->options);
        $this->assertSame([], $line->choices());
    }

    public function test_addons_alone_when_variants_are_switched_off(): void
    {
        $this->shop->update(['switched_off' => ['variants']]);
        $id = $this->ids();

        $this->order([['dish_id' => $this->burger->id, 'quantity' => 1, 'addons' => [$id['Extra cheese']]]])
            ->assertCreated()
            ->assertJsonPath('data.total', '9.00');

        $this->assertSame(['+ Extra cheese'], $this->shop->orders()->first()->items()->first()->choices());
    }

    public function test_the_whatsapp_message_lists_the_choices_under_the_line(): void
    {
        $id = $this->ids();

        $response = $this->order([[
            'dish_id' => $this->burger->id,
            'quantity' => 1,
            'options' => [$id['Large'], $id['Hot']],
            'addons' => [$id['Extra cheese']],
        ]])->assertCreated();

        $message = rawurldecode((string) parse_url($response->json('data.whatsapp_url'), PHP_URL_QUERY));
        $this->assertStringContainsString("1 × Burger  \$12.00\n    Size: Large, Spice level: Hot, + Extra cheese\n", $message);
    }

    public function test_the_shape_of_the_choices_is_checked(): void
    {
        $this->order([['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => 'large', 'addons' => ['bacon']]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.options', 'items.0.addons.0']);

        $this->order([['dish_id' => $this->burger->id, 'quantity' => 1, 'addons' => range(1, 21)]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['items.0.addons']);
    }
}
