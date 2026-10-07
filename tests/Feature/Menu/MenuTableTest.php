<?php

namespace Tests\Feature\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The menu opened from a table's QR code: it says which table, and the cart
 * can order to it.
 */
class MenuTableTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private DiningTable $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::DineIn, Feature::MultipleLanguages);
        $this->shop = $this->published(['slug' => 'olive', 'order_mode' => 'menu', 'second_locale' => 'ar']);
        $this->table = DiningTable::factory()->for($this->shop)->create(['name' => 'Terrace 2']);
        Dish::factory()->create([
            'restaurant_id' => $this->shop->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $this->shop->id])->id,
            'price' => '6.00',
            'is_available' => true,
        ]);
    }

    public function test_the_tables_code_opens_the_menu_at_that_table(): void
    {
        $html = $this->get(route('public.menu', ['olive', 'table' => $this->table->code, 'qr' => 1]))->assertOk()->getContent();

        $this->assertStringContainsString('Your table', $html);
        $this->assertStringContainsString('Terrace 2', $html);
        // The cart gets the code to send with the order (through @js).
        $this->assertStringContainsString('table: JSON.parse(\'{\\u0022code\\u0022:\\u0022'.$this->table->code.'\\u0022', $html);
        $this->assertStringContainsString("dine_in: 'At my table'", $html);
    }

    public function test_switching_language_stays_at_the_table(): void
    {
        $html = $this->get(route('public.menu', ['olive', 'table' => $this->table->code]))->getContent();

        $this->assertStringContainsString('lang=ar&amp;table='.$this->table->code, $html);
    }

    public function test_an_unknown_code_opens_the_plain_menu(): void
    {
        $html = $this->get(route('public.menu', ['olive', 'table' => 'nottable12']))->assertOk()->getContent();

        $this->assertStringNotContainsString('Your table', $html);
        $this->assertStringContainsString('table: null', $html);
    }

    public function test_without_ordering_of_any_kind_the_table_is_not_mentioned(): void
    {
        $this->defaultPackageSets(Feature::Ordering, 0);
        $this->defaultPackageSets(Feature::DineIn, 0);

        $html = $this->get(route('public.menu', ['olive', 'table' => $this->table->code]))->assertOk()->getContent();

        $this->assertStringNotContainsString('Terrace 2', $html);
        $this->assertStringNotContainsString('menu-cart.js', $html);
    }

    public function test_at_a_table_the_cart_orders_in_the_menu_even_while_orders_go_to_whatsapp(): void
    {
        $this->shop->update(['order_mode' => 'whatsapp']);

        $html = $this->get(route('public.menu', ['olive', 'table' => $this->table->code]))->assertOk()->getContent();

        $this->assertStringContainsString("mode: 'menu'", $html);
        // Delivery and pickup stay on WhatsApp, so only the table is offered.
        $this->assertStringContainsString('types: [],', $html);
        $this->assertStringContainsString('dineIn: true', $html);

        // Away from a table the cart goes to WhatsApp as before.
        $this->assertStringContainsString("mode: 'whatsapp'", $this->get(route('public.menu', 'olive'))->getContent());
    }

    public function test_ordering_at_the_table_alone_still_gives_a_table_its_cart(): void
    {
        $this->defaultPackageSets(Feature::Ordering, 0);

        $this->get(route('public.menu', ['olive', 'table' => $this->table->code]))
            ->assertOk()
            ->assertSee('menu-cart.js', false);
        $this->get(route('public.menu', 'olive'))->assertDontSee('menu-cart.js', false);
    }

    public function test_a_scan_of_a_table_counts_as_a_qr_scan(): void
    {
        $this->get($this->table->menuUrl())->assertOk();

        $this->assertTrue($this->shop->menuSessions()->sole()->via_qr);
    }
}
