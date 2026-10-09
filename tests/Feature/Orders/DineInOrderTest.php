<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Ordering from the seat: each table's QR code opens the menu at that table,
 * and an order "at my table" goes to it, with no address and no need for a
 * name or a number.
 */
class DineInOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const TOKEN = '1b4e28ba-2fa1-41d2-883f-0016d3cca427';

    private Restaurant $shop;

    private DiningTable $table;

    private Dish $dish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::DineIn, Feature::MultipleLanguages);
        $this->shop = $this->published([
            'slug' => 'olive', 'currency' => 'USD', 'order_mode' => 'menu', 'second_locale' => 'ar',
            'country_code' => 'LB', 'phone' => '70123456',
        ]);
        $this->table = DiningTable::factory()->for($this->shop)->create(['name' => 'Table 4']);
        $this->dish = Dish::factory()->create([
            'restaurant_id' => $this->shop->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $this->shop->id])->id,
            'name' => ['en' => 'Kebab'],
            'price' => '6.00',
            'is_available' => true,
        ]);
    }

    /** What the cart sends for an order at the table, with anything overridden. */
    private function atTable(array $overrides = []): array
    {
        return array_merge([
            'items' => [['dish_id' => $this->dish->id, 'quantity' => 2]],
            'mode' => 'menu',
            'fulfilment' => 'dine_in',
            'table' => $this->table->code,
            'name' => '',
            'phone_country' => 'LB',
            'phone' => '',
            'note' => 'No onions',
            'client_token' => self::TOKEN,
            'website' => '',
        ], $overrides);
    }

    public function test_an_order_at_the_table_goes_to_it_without_a_name_or_number(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable())->assertCreated();

        $order = Order::query()->sole();
        $this->assertSame(OrderChannel::Menu, $order->channel);
        $this->assertSame(Fulfilment::DineIn, $order->fulfilment);
        $this->assertSame($this->table->id, $order->table_id);
        $this->assertSame('Table 4', $order->table_name);
        $this->assertNull($order->guest_name);
        $this->assertNull($order->guest_phone);
        $this->assertNull($order->address);
        $this->assertNotNull($order->tracking_token);
    }

    public function test_a_name_and_number_given_at_the_table_are_kept_and_checked(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable(['phone' => '123']))
            ->assertJsonValidationErrors(['phone' => 'Check your phone number.']);

        $this->postJson(route('public.order', 'olive'), $this->atTable(['name' => 'Rami', 'phone' => '70 123 456']))
            ->assertCreated();

        $order = Order::query()->sole();
        $this->assertSame('Rami', $order->guest_name);
        $this->assertSame('+96170123456', $order->guest_phone);
    }

    public function test_an_address_sent_along_is_not_kept(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable(['address' => 'Hamra', 'latitude' => 33.9, 'longitude' => 35.5]))
            ->assertCreated();

        $this->assertNull(Order::query()->sole()->address);
        $this->assertNull(Order::query()->sole()->latitude);
    }

    public function test_dine_in_needs_the_tables_code(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable(['table' => null]))
            ->assertJsonValidationErrors(['table' => 'Scan the code on your table to order to it.']);

        $this->assertSame(0, Order::query()->count());
    }

    public function test_a_code_the_owner_replaced_is_no_table(): void
    {
        $old = $this->table->code;
        $this->table->newCode();

        $this->postJson(route('public.order', 'olive'), $this->atTable(['table' => $old]))
            ->assertJsonValidationErrors(['table' => "This table's code has changed. Scan the code on your table again."]);
    }

    public function test_another_restaurants_table_is_no_table_here(): void
    {
        $theirs = DiningTable::factory()->create();

        $this->postJson(route('public.order', 'olive'), $this->atTable(['table' => $theirs->code]))
            ->assertJsonValidationErrors(['table']);
    }

    public function test_not_while_ordering_at_the_table_is_switched_off(): void
    {
        $this->shop->update(['switched_off' => ['dine_in']]);

        $this->postJson(route('public.order', 'olive'), $this->atTable())
            ->assertJsonValidationErrors(['fulfilment' => 'Choose how you would like your order.']);
    }

    public function test_not_without_the_package_feature(): void
    {
        $this->defaultPackageSets(Feature::DineIn, 0);

        $this->postJson(route('public.order', 'olive'), $this->atTable())
            ->assertJsonValidationErrors(['fulfilment']);
    }

    public function test_at_the_table_orders_come_in_the_menu_while_the_rest_go_to_whatsapp(): void
    {
        $this->shop->update(['order_mode' => 'whatsapp']);

        $this->postJson(route('public.order', 'olive'), $this->atTable())
            ->assertCreated()
            ->assertJsonPath('data.channel', 'menu')
            ->assertJsonPath('data.whatsapp_url', null);

        $this->assertSame(OrderChannel::Menu, Order::query()->sole()->channel);

        // A delivery from the same page is still not taken in the menu.
        $this->postJson(route('public.order', 'olive'), $this->atTable([
            'fulfilment' => 'delivery', 'name' => 'Rami', 'phone' => '70 123 456', 'address' => 'Hamra',
            'client_token' => 'dddddddd-2fa1-41d2-883f-0016d3cca427',
        ]))->assertConflict();
        $this->assertSame(1, Order::query()->count());
    }

    public function test_at_the_table_works_with_ordering_switched_off(): void
    {
        $this->shop->update(['switched_off' => ['orders']]);

        $this->postJson(route('public.order', 'olive'), $this->atTable())->assertCreated();
    }

    public function test_a_change_at_the_table_needs_only_ordering_at_the_table(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable())->assertCreated();
        $order = Order::query()->sole();
        $this->shop->update(['order_mode' => 'whatsapp']);

        $this->putJson(route('public.order.update', ['olive', $order->tracking_token]), $this->atTable([
            'version' => 0, 'client_token' => 'cccccccc-2fa1-41d2-883f-0016d3cca427',
        ]))->assertOk();
    }

    public function test_a_delivery_still_asks_who_and_where_and_never_carries_the_table(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable(['fulfilment' => 'delivery']))
            ->assertJsonValidationErrors(['name', 'phone', 'address']);

        $this->postJson(route('public.order', 'olive'), $this->atTable([
            'fulfilment' => 'pickup', 'name' => 'Rami', 'phone' => '70 123 456',
        ]))->assertCreated();

        $this->assertNull(Order::query()->sole()->table_name);
    }

    public function test_a_change_away_from_the_table_keeps_the_table(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable())->assertCreated();
        $order = Order::query()->sole();

        // From the tracking sheet, which does not have the code.
        $this->putJson(route('public.order.update', ['olive', $order->tracking_token]), $this->atTable([
            'table' => null,
            'items' => [['dish_id' => $this->dish->id, 'quantity' => 3]],
            'version' => 0,
            'client_token' => 'cccccccc-2fa1-41d2-883f-0016d3cca427',
        ]))->assertOk();

        $order->refresh();
        $this->assertSame('18.00', (string) $order->total);
        $this->assertSame('Table 4', $order->table_name);
        $this->assertSame($this->table->id, $order->table_id);
    }

    public function test_a_change_to_pickup_lets_go_of_the_table(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable())->assertCreated();
        $order = Order::query()->sole();

        $this->putJson(route('public.order.update', ['olive', $order->tracking_token]), $this->atTable([
            'fulfilment' => 'pickup', 'name' => 'Rami', 'phone' => '70 123 456',
            'version' => 0, 'client_token' => 'cccccccc-2fa1-41d2-883f-0016d3cca427',
        ]))->assertOk();

        $this->assertNull($order->fresh()->table_name);
    }

    public function test_the_tracking_page_says_it_is_coming_to_the_table(): void
    {
        $this->postJson(route('public.order', 'olive'), $this->atTable())->assertCreated();
        $order = Order::query()->sole();
        $order->moveTo(OrderStatus::Ready);

        $this->getJson(route('public.order.track', ['olive', $order->tracking_token]))
            ->assertOk()
            ->assertJsonPath('data.fulfilment', 'dine_in')
            ->assertJsonPath('data.table', 'Table 4')
            ->assertJsonPath('data.html', fn (string $html): bool => str_contains($html, 'Coming to your table')
                && str_contains($html, 'Table 4')
                && ! str_contains($html, 'Directions'));

        $this->getJson(route('public.order.cart', ['olive', $order->tracking_token]))
            ->assertJsonPath('data.details.table', 'Table 4');
    }

    /** What the cart sends for an order at the table that goes to WhatsApp. */
    private function onWhatsApp(array $overrides = []): array
    {
        return array_merge([
            'items' => [['dish_id' => $this->dish->id, 'quantity' => 1]],
            'mode' => 'whatsapp',
            'table' => $this->table->code,
            'locale' => 'ar',
        ], $overrides);
    }

    public function test_table_orders_can_go_to_whatsapp_with_the_table_on_top(): void
    {
        $this->shop->update(['dine_in_mode' => 'whatsapp', 'name' => ['en' => 'Olive', 'ar' => 'زيتون']]);
        $this->dish->update(['name' => ['en' => 'Kebab', 'ar' => 'كباب']]);

        $response = $this->postJson(route('public.order', 'olive'), $this->onWhatsApp())
            ->assertCreated()
            ->assertJsonPath('data.channel', 'whatsapp');

        $text = rawurldecode((string) parse_url((string) $response->json('data.whatsapp_url'), PHP_URL_QUERY));
        // The guest read Arabic; the owner wrote the menu in English.
        $this->assertStringStartsWith("text=*New order · Olive*\n*Table 4*\n\n*1 × Kebab*   \$6\n", $text);

        $order = Order::query()->sole();
        $this->assertSame(OrderChannel::WhatsApp, $order->channel);
        $this->assertSame('Table 4', $order->table_name);
        $this->assertSame('Kebab', $order->items->sole()->name);
        // Not listed on the dashboard's Table orders page.
        $this->assertSame(0, $this->shop->orders()->inMenu()->count());
    }

    public function test_while_table_orders_go_to_whatsapp_a_page_ordering_in_the_menu_is_told_to_refresh(): void
    {
        $this->shop->update(['dine_in_mode' => 'whatsapp']);

        $this->postJson(route('public.order', 'olive'), $this->atTable())->assertConflict();
        $this->assertSame(0, Order::query()->count());
    }

    public function test_table_orders_on_whatsapp_work_with_ordering_switched_off(): void
    {
        $this->shop->update(['dine_in_mode' => 'whatsapp', 'switched_off' => ['orders']]);

        $this->postJson(route('public.order', 'olive'), $this->onWhatsApp())->assertCreated();
    }

    public function test_without_a_number_table_orders_stay_on_the_dashboard(): void
    {
        $this->shop->update(['dine_in_mode' => 'whatsapp', 'phone' => null]);

        $this->postJson(route('public.order', 'olive'), $this->onWhatsApp())->assertConflict();
        $this->postJson(route('public.order', 'olive'), $this->atTable())
            ->assertCreated()
            ->assertJsonPath('data.channel', 'menu');
    }

    public function test_without_ordering_at_the_table_a_tables_code_only_labels_a_whatsapp_order(): void
    {
        $this->shop->update(['order_mode' => 'whatsapp', 'switched_off' => ['dine_in']]);

        $response = $this->postJson(route('public.order', 'olive'), $this->onWhatsApp(['locale' => 'en']))->assertCreated();

        $this->assertStringContainsString("*Table 4*\n", rawurldecode((string) $response->json('data.whatsapp_url')));
        $this->assertSame('Table 4', Order::query()->sole()->table_name);
    }

    public function test_on_whatsapp_an_old_code_never_stops_the_order(): void
    {
        $this->shop->update(['order_mode' => 'whatsapp']);

        $this->postJson(route('public.order', 'olive'), [
            'items' => [['dish_id' => $this->dish->id, 'quantity' => 1]],
            'mode' => 'whatsapp',
            'table' => 'gone12345',
        ])->assertCreated();

        $this->assertNull(Order::query()->sole()->table_name);
    }
}
