<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishBuild;
use App\Models\DishBuildChoice;
use App\Models\Restaurant;
use App\Support\CategoryIcons;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A dish's "build your own" on the public menu (MenuDishOptions): in the
 * page's #dish-options for the sheet to draw and price, only while the
 * restaurant has the feature, with the choices guests can buy.
 */
class MenuDishBuildsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $pizza;

    private Dish $margherita;

    private Dish $pepperoni;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::BuildYourOwn);
        $this->shop = $this->published(['slug' => 'olive']);
        $pizzas = Category::factory()->for($this->shop)->create(['name' => ['en' => 'Pizzas'], 'display_order' => 1]);
        $this->margherita = Dish::factory()->for($this->shop)->create(['category_id' => $pizzas->id, 'name' => ['en' => 'Margherita'], 'price' => '12.00', 'display_order' => 1]);
        $this->pepperoni = Dish::factory()->for($this->shop)->create(['category_id' => $pizzas->id, 'name' => ['en' => 'Pepperoni'], 'price' => '16.00', 'display_order' => 2]);
        Dish::factory()->for($this->shop)->create(['category_id' => $pizzas->id, 'name' => ['en' => 'Sold out'], 'price' => '9.00', 'is_available' => false]);
        Dish::factory()->for($this->shop)->create(['category_id' => $pizzas->id, 'name' => ['en' => 'Unpriced'], 'price' => null]);
        $this->pizza = Dish::factory()->for($this->shop)->create(['category_id' => $pizzas->id, 'name' => ['en' => 'Build your pizza'], 'price' => null, 'display_order' => 3]);
        $build = DishBuild::factory()->for($this->pizza)->create(['name' => ['en' => 'Your pizza'], 'parts' => [2], 'category_id' => $pizzas->id]);
        DishBuildChoice::factory()->create(['dish_build_id' => $build->id, 'dish_id' => $this->margherita->id, 'name' => null, 'price' => null, 'part_prices' => ['2' => '7.00']]);
    }

    /** @return array<string, mixed> the page's #dish-options data */
    private function choices(TestResponse $response): array
    {
        preg_match('#<script type="application/json" id="dish-options">(.*?)</script>#s', (string) $response->getContent(), $match);

        return json_decode($match[1] ?? 'null', true) ?? [];
    }

    public function test_a_pizza_built_from_the_pizzas_guests_can_buy(): void
    {
        $sheet = $this->choices($this->get(route('public.menu', $this->shop->slug))->assertOk())[$this->pizza->id];

        $this->assertSame([[
            'id' => $this->pizza->builds()->sole()->id,
            'name' => 'Your pizza',
            'shape' => 'split',
            'parts' => [2],
            // Every pizza has a price of its own: no sizes to pick.
            'sizes' => [],
            'choices' => [
                ['key' => 'd'.$this->margherita->id, 'name' => 'Margherita', 'price' => '12.00', 'part_prices' => ['2' => '7.00'], 'sizes' => []],
                ['key' => 'd'.$this->pepperoni->id, 'name' => 'Pepperoni', 'price' => '16.00', 'part_prices' => [], 'sizes' => []],
            ],
        ]], $sheet['builds']);
        // Two of Margherita's $7 halves: the least it costs.
        $this->assertSame('14.00', $sheet['lowest']);
        $this->assertTrue($sheet['priced']);
    }

    public function test_a_build_offers_only_the_dishes_the_owner_ticked(): void
    {
        $hawaiian = Dish::factory()->for($this->shop)->create(['category_id' => $this->margherita->category_id, 'name' => ['en' => 'Hawaiian'], 'price' => '14.00', 'display_order' => 4]);
        $this->pizza->builds()->sole()->update(['dish_ids' => [$hawaiian->id, $this->margherita->id]]);

        $sheet = $this->choices($this->get(route('public.menu', $this->shop->slug))->assertOk())[$this->pizza->id];

        // In the menu's order, Pepperoni left out.
        $this->assertSame(['d'.$this->margherita->id, 'd'.$hawaiian->id], array_column($sheet['builds'][0]['choices'], 'key'));
    }

    public function test_a_builds_dish_sits_above_the_search_with_its_icon_in_no_category(): void
    {
        // One filed under the pizzas from before, one in no category.
        $this->pizza->update(['icon' => 'pizza']);
        $scoops = Dish::factory()->for($this->shop)->create(['category_id' => null, 'name' => ['en' => 'Ice cream'], 'price' => null, 'icon' => 'ice-cream', 'display_order' => 9]);
        DishBuild::factory()->for($scoops)->pieces()->withChoices(['Vanilla' => 2, 'Mango' => 2])->create();
        // In no category and no build: not on the menu.
        Dish::factory()->for($this->shop)->create(['category_id' => null, 'name' => ['en' => 'Loose']]);

        $page = (string) $this->get(route('public.menu', $this->shop->slug))->assertOk()->getContent();

        // The row ends where the search begins.
        $start = (int) strpos($page, '<section class="builds"');
        $strip = substr($page, $start, (int) strpos($page, '<div class="search">') - $start);
        $this->assertGreaterThan(0, $start);
        $this->assertStringContainsString('data-dish="'.$this->pizza->id.'"', $strip);
        $this->assertStringContainsString('data-dish="'.$scoops->id.'"', $strip);
        $this->assertStringContainsString(CategoryIcons::svg('ice-cream'), $strip);
        // Each once: out of the pizzas' list.
        $this->assertSame(1, substr_count($page, 'data-dish="'.$this->pizza->id.'"'));
        $this->assertStringNotContainsString('Loose', $page);
        $this->assertArrayHasKey($scoops->id, $this->choices($this->get(route('public.menu', $this->shop->slug))));
    }

    public function test_a_build_with_one_choice_left_is_not_offered(): void
    {
        $this->pepperoni->update(['is_available' => false]);

        $this->assertArrayNotHasKey($this->pizza->id, $this->choices($this->get(route('public.menu', $this->shop->slug))));
    }

    public function test_without_the_feature_the_menu_says_nothing_of_it(): void
    {
        $this->defaultPackageSets(Feature::BuildYourOwn, 0);

        $this->assertArrayNotHasKey($this->pizza->id, $this->choices($this->get(route('public.menu', $this->shop->slug))));
    }
}
