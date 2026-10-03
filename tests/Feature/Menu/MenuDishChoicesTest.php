<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A dish's variants and add-ons on the public menu: the card says there is
 * a choice, and one sheet (menu-dish.js) draws it from the page's data.
 */
class MenuDishChoicesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Category $plates;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Variants, Feature::Addons, Feature::Ordering, Feature::MultipleLanguages);
        $this->shop = $this->published(['slug' => 'olive', 'currency' => 'USD', 'default_locale' => 'en', 'second_locale' => 'ar']);
        $this->plates = Category::factory()->create(['restaurant_id' => $this->shop->id, 'name' => ['en' => 'Plates']]);
    }

    private function dish(array $attributes = []): Dish
    {
        return Dish::factory()->create([
            'restaurant_id' => $this->shop->id,
            'category_id' => $this->plates->id,
            'name' => ['en' => 'Burger'],
            'price' => '8.00',
            ...$attributes,
        ]);
    }

    private function menu(string $query = ''): TestResponse
    {
        return $this->get(route('public.menu', $this->shop->slug).$query)->assertOk();
    }

    /** @return array<string, mixed> the page's #dish-options data */
    private function choices(TestResponse $response): array
    {
        preg_match('#<script type="application/json" id="dish-options">(.*?)</script>#s', (string) $response->getContent(), $match);

        return json_decode($match[1] ?? 'null', true) ?? [];
    }

    public function test_a_dish_with_choices_says_so_and_carries_its_sheet(): void
    {
        $burger = Dish::factory()
            ->withVariants(['Size' => ['Small' => 0, 'Large' => 3], 'Spice level' => ['Mild' => 0, 'Hot' => 0]])
            ->withAddons(['Extra cheese' => 1])
            ->create(['restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id, 'name' => ['en' => 'Burger'], 'price' => '8.00']);

        $response = $this->menu()
            ->assertSee('class="dish has-choices"', false)
            ->assertSee('data-choices', false)
            // The card names no choices; the sheet holds them.
            ->assertDontSee('Size · Spice level')
            // The least a guest can pay, with no "from" in front of it.
            ->assertSee('<span class="price">$8.00</span>', false)
            ->assertDontSee('>from<', false)
            ->assertSee('id="dish-sheet"', false)
            ->assertSee('menu-dish.js', false)
            ->assertSee('Pick 1')
            ->assertSee('Optional')
            // A choice with no price is called free in the sheet.
            ->assertSee("free: 'Free'", false);

        $data = $this->choices($response)[$burger->id];
        $this->assertSame(['Size', 'Spice level'], array_column($data['variants'], 'name'));
        $this->assertSame([['Small', '0.00'], ['Large', '3.00']], array_map(fn (array $option): array => [$option['name'], $option['price']], $data['variants'][0]['options']));
        $this->assertSame('Extra cheese', $data['addons'][0]['name']);
        $this->assertSame('8.00', $data['lowest']);
        $this->assertTrue($data['priced']);
    }

    public function test_the_card_price_starts_at_the_cheapest_choice(): void
    {
        // Every size costs more than the dish alone, so the card cannot say $5.
        $this->dish(['price' => '5.00'])->variants()->create(['name' => ['en' => 'Size']])->options()->createMany([
            ['name' => ['en' => 'Medium'], 'price' => 1],
            ['name' => ['en' => 'Large'], 'price' => 2],
        ]);

        $this->menu()->assertSee('$6.00')->assertDontSee('$5.00');
    }

    public function test_the_guests_language_names_the_choices(): void
    {
        $burger = Dish::factory()->withVariants(['Size' => ['Small' => 0, 'Large' => 3]])->create([
            'restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id, 'price' => '8.00',
        ]);
        $burger->variants->first()->setTranslation('name', 'ar', 'الحجم')->save();

        $response = $this->menu('?lang=ar')
            ->assertSee(trim((string) json_encode('مجانًا'), '"'), false)
            // The sheet's labels, as the script reads them (@js escapes them).
            ->assertSee(trim((string) json_encode('اختر واحدًا'), '"'), false);
        $this->assertSame('الحجم', $this->choices($response)[$burger->id]['variants'][0]['name']);
        // A choice with no Arabic falls back to English, never blank.
        $this->assertSame('Small', $this->choices($response)[$burger->id]['variants'][0]['options'][0]['name']);
    }

    public function test_without_ordering_the_sheet_only_shows_the_choices(): void
    {
        $this->defaultPackageSets(Feature::Ordering, 0);
        Dish::factory()->withAddons(['Extra cheese' => 1])->create(['restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id]);

        $this->menu()
            ->assertSee('See options')
            ->assertSee('id="dish-sheet"', false)
            ->assertSee('canOrder: false', false)
            ->assertDontSee('menu-cart.js', false);
    }

    public function test_a_switched_off_list_leaves_the_menu(): void
    {
        $burger = Dish::factory()->withVariants()->withAddons()->create([
            'restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id,
        ]);
        $this->shop->update(['switched_off' => ['variants']]);

        $data = $this->choices($this->menu())[$burger->id];
        $this->assertSame([], $data['variants']);
        $this->assertCount(2, $data['addons']);

        $this->shop->update(['switched_off' => ['variants', 'addons']]);
        $this->menu()->assertDontSee('id="dish-sheet"', false)->assertDontSee('has-choices', false)->assertDontSee('menu-dish.js', false);
    }

    public function test_a_package_without_them_keeps_the_menu_plain(): void
    {
        $this->defaultPackageSets(Feature::Variants, 0);
        $this->defaultPackageSets(Feature::Addons, 0);
        Dish::factory()->withVariants()->withAddons()->create(['restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id]);

        $this->menu()->assertDontSee('id="dish-sheet"', false)->assertDontSee('has-choices', false);
    }

    public function test_a_dish_priced_by_its_sizes_starts_at_the_smallest(): void
    {
        // No price of its own: Small $7, Large $12, and add-ons on top.
        $sandwich = Dish::factory()
            ->withVariants(['Size' => ['Small' => 7, 'Large' => 12], 'Spice level' => ['Mild' => 0, 'Hot' => 0.5]])
            ->withAddons(['Cheese' => 1])
            ->create(['restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id, 'name' => ['en' => 'Sandwich'], 'price' => null]);

        $response = $this->menu()
            ->assertSee('<span class="price">$7.00</span>', false)
            ->assertSee('data-dish="'.$sandwich->id.'"', false);
        $this->assertMatchesRegularExpression('/data-dish="'.$sandwich->id.'"[^>]*data-price="0.00"/', (string) $response->getContent());

        $data = $this->choices($response)[$sandwich->id];
        $this->assertFalse($data['priced']);
        $this->assertSame('7.00', $data['lowest']);
    }

    public function test_nothing_to_choose_is_not_a_choice(): void
    {
        // A single option, and a dish with no price for a choice to add to.
        Dish::factory()->withVariants(['Bread' => ['Saj' => 0]])->create(['restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id]);
        Dish::factory()->withAddons()->create(['restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id, 'price' => null]);

        $this->menu()->assertDontSee('id="dish-sheet"', false)->assertDontSee('has-choices', false);
    }

    public function test_the_page_data_cannot_break_out_of_its_script(): void
    {
        $burger = Dish::factory()->withAddons(['</script><script>alert(1)</script>' => 1])->create([
            'restaurant_id' => $this->shop->id, 'category_id' => $this->plates->id,
        ]);

        $response = $this->menu()->assertDontSee('<script>alert(1)', false);
        $this->assertSame('</script><script>alert(1)</script>', $this->choices($response)[$burger->id]['addons'][0]['name']);
    }
}
