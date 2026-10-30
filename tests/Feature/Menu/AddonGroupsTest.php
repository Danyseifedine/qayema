<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishAddonGroup;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * Add-ons in a group ("Sauces: pick up to 2"): the menu's sheet gets each
 * group with its fewest and most, an order keeps to them and remembers the
 * group's name, the dashboard sees them but its form never touches them.
 */
class AddonGroupsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Dish $hummus;

    private DishAddonGroup $sauces;

    /** @var array<string, int> add-on ids by English name */
    private array $id = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::Addons, Feature::MultipleLanguages);
        $this->shop = $this->published(['slug' => 'olive', 'currency' => 'USD', 'country_code' => 'LB', 'phone' => '70123456', 'second_locale' => 'ar']);
        $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
        $this->hummus = Dish::factory()->create(['restaurant_id' => $this->shop->id, 'category_id' => $category->id, 'name' => ['en' => 'Hummus', 'ar' => 'حمص'], 'price' => '5.00']);
        $this->sauces = DishAddonGroup::factory()->create(['dish_id' => $this->hummus->id, 'name' => ['en' => 'Sauces', 'ar' => 'صلصات'], 'min_select' => 1, 'max_select' => 2]);

        foreach (['Garlic' => 0.5, 'Chili' => 0.5, 'Tahini' => 1] as $name => $price) {
            $this->id[$name] = DishAddon::factory()->create(['dish_id' => $this->hummus->id, 'dish_addon_group_id' => $this->sauces->id, 'name' => ['en' => $name], 'price' => $price])->id;
        }

        $this->id['Bread'] = DishAddon::factory()->create(['dish_id' => $this->hummus->id, 'name' => ['en' => 'Bread'], 'price' => 1])->id;
    }

    /** @return array<string, mixed> the page's #dish-options data */
    private function choices(TestResponse $response): array
    {
        preg_match('#<script type="application/json" id="dish-options">(.*?)</script>#s', (string) $response->getContent(), $match);

        return json_decode($match[1] ?? 'null', true) ?? [];
    }

    /** @param  array<int, string>  $addons */
    private function order(array $addons, array $extra = []): TestResponse
    {
        return $this->postJson(route('public.order', $this->shop->slug), [
            'items' => [['dish_id' => $this->hummus->id, 'quantity' => 1, 'addons' => array_map(fn (string $name): int => $this->id[$name], $addons)]],
            ...$extra,
        ]);
    }

    public function test_the_sheet_gets_each_group_with_its_limits_apart_from_the_dishs_own_add_ons(): void
    {
        $sheet = $this->choices($this->get(route('public.menu', $this->shop->slug))->assertOk())[$this->hummus->id];

        $this->assertSame([['id' => $this->id['Bread'], 'name' => 'Bread', 'price' => '1.00']], $sheet['addons']);
        $this->assertSame([[
            'id' => $this->sauces->id,
            'name' => 'Sauces',
            'min' => 1,
            'max' => 2,
            'addons' => [
                ['id' => $this->id['Garlic'], 'name' => 'Garlic', 'price' => '0.50'],
                ['id' => $this->id['Chili'], 'name' => 'Chili', 'price' => '0.50'],
                ['id' => $this->id['Tahini'], 'name' => 'Tahini', 'price' => '1.00'],
            ],
        ]], $sheet['addon_groups']);
    }

    public function test_a_group_never_asks_more_than_it_has_and_a_most_that_limits_nothing_is_dropped(): void
    {
        $this->sauces->update(['min_select' => 5, 'max_select' => 9]);

        $group = $this->choices($this->get(route('public.menu', $this->shop->slug)))[$this->hummus->id]['addon_groups'][0];

        $this->assertSame([3, null], [$group['min'], $group['max']]);
    }

    public function test_the_sheet_speaks_the_guests_language(): void
    {
        $group = $this->choices($this->get(route('public.menu', [$this->shop->slug, 'lang' => 'ar'])))[$this->hummus->id]['addon_groups'][0] ?? null;

        $this->assertSame('صلصات', $group['name'] ?? null);
    }

    public function test_a_dish_with_no_price_offers_no_add_ons_grouped_or_not(): void
    {
        $this->hummus->update(['price' => null]);

        $this->assertArrayNotHasKey($this->hummus->id, $this->choices($this->get(route('public.menu', $this->shop->slug))));
    }

    public function test_an_order_within_the_limits_keeps_the_group_name_on_each_add_on(): void
    {
        $this->order(['Tahini', 'Garlic', 'Bread'])->assertCreated();

        $line = Order::query()->sole()->items()->sole();
        $this->assertSame('7.50', (string) $line->unit_price);
        $this->assertSame([
            ['name' => 'Garlic', 'price' => '0.50', 'addon_id' => $this->id['Garlic'], 'group' => 'Sauces'],
            ['name' => 'Tahini', 'price' => '1.00', 'addon_id' => $this->id['Tahini'], 'group' => 'Sauces'],
            ['name' => 'Bread', 'price' => '1.00', 'addon_id' => $this->id['Bread']],
        ], $line->options['addons']);
    }

    public function test_too_few_or_too_many_from_a_group_is_refused_and_said_plainly(): void
    {
        $this->order(['Bread'])->assertJsonValidationErrors(['items' => 'Pick at least 1 from Sauces for Hummus.']);
        $this->order(['Garlic', 'Chili', 'Tahini'])->assertJsonValidationErrors(['items' => 'Pick no more than 2 from Sauces for Hummus.']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_the_refusal_comes_in_the_guests_language(): void
    {
        $this->order([], ['locale' => 'ar'])->assertJsonValidationErrors(['items' => 'اختر 1 على الأقل من صلصات لطبق حمص.']);
    }

    public function test_the_dashboard_sees_the_groups_apart_and_its_form_leaves_them_alone(): void
    {
        $owner = $this->shop->user()->firstOrFail();

        $this->actingAs($owner)->getJson(route('api.dishes.index'))
            ->assertOk()
            ->assertJsonPath('data.0.addons', [['id' => $this->id['Bread'], 'name' => ['en' => 'Bread', 'ar' => null], 'price' => '1.00']])
            ->assertJsonPath('data.0.addon_groups.0.name', ['en' => 'Sauces', 'ar' => 'صلصات'])
            ->assertJsonPath('data.0.addon_groups.0.min_select', 1)
            ->assertJsonPath('data.0.addon_groups.0.max_select', 2)
            ->assertJsonPath('data.0.addon_groups.0.addons.2.name.en', 'Tahini')
            ->assertJsonPath('data.0.from_pos', false)
            ->assertJsonPath('data.0.pos_removed', false);

        $this->hummus->forceFill(['pos_id' => '301', 'pos_removed_at' => now()])->save();
        $this->actingAs($owner)->getJson(route('api.dishes.index'))
            ->assertJsonPath('data.0.from_pos', true)
            ->assertJsonPath('data.0.pos_removed', true);

        // The form sends only its own add-ons: the grouped ones stay as they were.
        $this->actingAs($owner)->patchJson(route('api.dishes.update', $this->hummus), [
            'addons' => [['id' => $this->id['Bread'], 'name' => ['en' => 'Pita'], 'price' => 1]],
        ])->assertOk();

        $this->assertSame(4, DishAddon::query()->where('dish_id', $this->hummus->id)->count());
        $this->assertSame(3, DishAddon::query()->where('dish_addon_group_id', $this->sauces->id)->count());

        $this->actingAs($owner)->patchJson(route('api.dishes.update', $this->hummus), ['addons' => []])->assertOk();

        $this->assertSame(3, DishAddon::query()->where('dish_id', $this->hummus->id)->count());
    }
}
