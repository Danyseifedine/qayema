<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What a WhatsApp order asks the guest for, as the owner set it
 * (Restaurant::whatsappAsks()): each of name, phone and, for delivery and
 * pickup, an address that also asks which of the two, off, optional or
 * required. What was given heads the message.
 */
class WhatsAppGuestDetailsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $dish;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::DineIn);
        $this->shop = $this->published([
            'slug' => 'olive', 'name' => ['en' => 'Olive'], 'currency' => 'USD', 'order_mode' => 'whatsapp',
            'country_code' => 'LB', 'phone' => '70123456',
        ]);
        $this->dish = Dish::factory()->create([
            'restaurant_id' => $this->shop->id,
            'category_id' => Category::factory()->create(['restaurant_id' => $this->shop->id])->id,
            'name' => ['en' => 'Kebab'],
            'price' => '6.00',
        ]);
    }

    private function asks(array $away = [], array $table = []): void
    {
        $this->shop->update(['whatsapp_fields' => [
            'away' => [...['name' => 'off', 'phone' => 'off', 'address' => 'off'], ...$away],
            'table' => [...['name' => 'off', 'phone' => 'off'], ...$table],
        ]]);
    }

    private function order(array $body = []): TestResponse
    {
        return $this->postJson(route('public.order', 'olive'), [
            'items' => [['dish_id' => $this->dish->id, 'quantity' => 1]],
            'mode' => 'whatsapp',
            ...$body,
        ]);
    }

    private function message(TestResponse $response): string
    {
        return rawurldecode((string) parse_url((string) $response->json('data.whatsapp_url'), PHP_URL_QUERY));
    }

    public function test_nothing_is_asked_until_the_owner_turns_it_on(): void
    {
        $response = $this->order(['name' => 'Rami', 'phone_country' => 'LB', 'phone' => '70 123 456', 'fulfilment' => 'delivery', 'address' => 'Hamra'])
            ->assertCreated();

        $order = Order::query()->sole();
        $this->assertNull($order->guest_name);
        $this->assertNull($order->guest_phone);
        $this->assertNull($order->fulfilment);
        $this->assertStringNotContainsString('Rami', $this->message($response));
    }

    public function test_a_required_name_is_asked_for_and_heads_the_message(): void
    {
        $this->asks(['name' => 'required']);

        $this->order()->assertUnprocessable()
            ->assertJsonValidationErrors(['name' => 'Add your name so the restaurant knows who to ask for.']);

        $response = $this->order(['name' => 'Rami'])->assertCreated();
        $this->assertSame('Rami', Order::query()->sole()->guest_name);
        $this->assertStringStartsWith("text=*New order · Olive*\nName: Rami\n\n", $this->message($response));
    }

    public function test_an_optional_phone_may_be_left_empty_but_not_wrong(): void
    {
        $this->asks(['phone' => 'optional']);

        $this->order(['phone_country' => 'LB', 'phone' => ''])->assertCreated();
        $this->order(['phone_country' => 'LB', 'phone' => '12'])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone' => 'Check your phone number.']);

        $response = $this->order(['phone_country' => 'LB', 'phone' => '70 123 456'])->assertCreated();
        $this->assertSame('+96170123456', Order::query()->latest('id')->first()->guest_phone);
        $this->assertStringContainsString("\nPhone: +96170123456\n", $this->message($response));
    }

    public function test_an_address_asks_delivery_or_pickup_and_is_wanted_only_for_a_delivery(): void
    {
        $this->asks(['address' => 'required']);

        $this->order()->assertUnprocessable()->assertJsonValidationErrors(['fulfilment']);
        $this->order(['fulfilment' => 'delivery'])->assertUnprocessable()
            ->assertJsonValidationErrors(['address' => 'Add your address for the delivery.']);

        $pickup = $this->order(['fulfilment' => 'pickup', 'address' => 'Ignored'])->assertCreated();
        $this->assertStringStartsWith("text=*New order · Olive*\n*Pickup*\n\n", $this->message($pickup));
        $this->assertNull(Order::query()->sole()->address);

        $delivery = $this->order([
            'fulfilment' => 'delivery', 'address' => 'Hamra, Bliss St', 'latitude' => 33.8959, 'longitude' => 35.4784,
        ])->assertCreated();
        $order = Order::query()->latest('id')->first();
        $this->assertSame(Fulfilment::Delivery, $order->fulfilment);
        $this->assertSame('Hamra, Bliss St', $order->address);
        $this->assertStringContainsString(
            "*Delivery*\nAddress: Hamra, Bliss St\nLocation: https://www.google.com/maps?q=33.8959,35.4784\n",
            $this->message($delivery),
        );
    }

    public function test_delivery_or_pickup_follows_what_the_restaurant_takes(): void
    {
        $this->asks(['address' => 'optional']);
        $this->shop->update(['order_types' => ['pickup']]);

        $this->order(['fulfilment' => 'delivery'])->assertUnprocessable()->assertJsonValidationErrors(['fulfilment']);
        $this->order(['fulfilment' => 'pickup'])->assertCreated();
    }

    public function test_an_order_at_the_table_asks_the_tables_set(): void
    {
        $table = DiningTable::factory()->for($this->shop)->create(['name' => 'Table 4']);
        $this->shop->update(['dine_in_mode' => 'whatsapp']);
        $this->asks(['name' => 'required', 'address' => 'required'], ['phone' => 'required']);

        // Neither the name nor the address delivery and pickup ask for.
        $this->order(['table' => $table->code])->assertUnprocessable()
            ->assertJsonValidationErrors(['phone'])
            ->assertJsonMissingValidationErrors(['name', 'address', 'fulfilment']);

        $response = $this->order(['table' => $table->code, 'phone_country' => 'LB', 'phone' => '70123456'])->assertCreated();
        $this->assertStringStartsWith("text=*New order · Olive*\n*Table 4*\nPhone: +96170123456\n\n", $this->message($response));
    }

    public function test_the_menu_tells_the_cart_what_to_ask(): void
    {
        $this->asks(['name' => 'optional', 'address' => 'required']);

        $html = (string) $this->get(route('public.menu', 'olive'))->assertOk()->getContent();

        $this->assertStringContainsString("mode: 'whatsapp'", $html);
        $this->assertStringContainsString('asks: JSON.parse(', $html);
        $this->assertStringContainsString('\\u0022name\\u0022:\\u0022optional\\u0022', $html);
        // Delivery or pickup comes with the address, and the fields' words.
        $this->assertStringContainsString("types: JSON.parse('[\\u0022delivery\\u0022,\\u0022pickup\\u0022]')", $html);
        $this->assertStringContainsString("name: 'Your name'", $html);
        $this->assertStringContainsString('guestKey:', $html);
    }

    public function test_a_cart_that_asks_nothing_carries_no_form(): void
    {
        $html = (string) $this->get(route('public.menu', 'olive'))->assertOk()->getContent();

        $this->assertStringContainsString("mode: 'whatsapp'", $html);
        $this->assertStringNotContainsString("name: 'Your name'", $html);
        $this->assertStringNotContainsString('guestKey:', $html);
    }
}
