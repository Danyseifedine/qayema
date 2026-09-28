<?php

namespace Tests\Integration\Services\Orders;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Restaurant;
use App\Services\Orders\WhatsAppLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppLinkTest extends TestCase
{
    use RefreshDatabase;

    private function shop(array $attributes = []): Restaurant
    {
        return Restaurant::factory()->create(array_merge([
            'name' => ['en' => 'Olive'],
            'default_locale' => 'en',
            'country_code' => 'LB',
            'phone' => '70123456',
            'currency' => 'USD',
        ], $attributes));
    }

    private function order(Restaurant $shop): Order
    {
        $order = Order::factory()->create([
            'restaurant_id' => $shop->id,
            'reference' => 'ABC234',
            'currency' => 'USD',
            'total' => '39.00',
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'name' => 'House Bowl',
            'unit_price' => '14.00',
            'quantity' => 2,
            'line_total' => '28.00',
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'name' => 'Daily Tart',
            'unit_price' => '11.00',
            'quantity' => 1,
            'line_total' => '11.00',
        ]);

        return $order->load('items');
    }

    public function test_the_national_number_gains_its_country_code(): void
    {
        $this->assertSame('96170123456', WhatsAppLink::internationalNumber($this->shop()));
    }

    public function test_a_trunk_zero_is_dropped(): void
    {
        // 03 004699 in Lebanon is +961 3 004699, never +961 03 004699.
        $shop = $this->shop(['phone' => '03 004699']);

        $this->assertSame('9613004699', WhatsAppLink::internationalNumber($shop));
    }

    public function test_a_number_already_carrying_its_code_is_left_alone(): void
    {
        $shop = $this->shop(['phone' => '+961 70 123 456']);

        $this->assertSame('96170123456', WhatsAppLink::internationalNumber($shop));
    }

    public function test_punctuation_and_spaces_are_stripped(): void
    {
        $shop = $this->shop(['phone' => '(70) 123-456']);

        $this->assertSame('96170123456', WhatsAppLink::internationalNumber($shop));
    }

    public function test_an_unknown_country_will_not_guess_a_code(): void
    {
        // Guessing here would message a stranger in another country.
        $shop = $this->shop(['country_code' => null, 'phone' => '70123456']);

        $this->assertNull(WhatsAppLink::internationalNumber($shop));
    }

    public function test_an_unknown_country_accepts_an_already_international_number(): void
    {
        $shop = $this->shop(['country_code' => null, 'phone' => '+96170123456']);

        $this->assertSame('96170123456', WhatsAppLink::internationalNumber($shop));
    }

    public function test_no_phone_means_no_link(): void
    {
        $shop = $this->shop(['phone' => null]);

        $this->assertNull(WhatsAppLink::forOrder($shop, $this->order($shop)));
    }

    public function test_the_message_carries_the_reference_lines_and_total(): void
    {
        $shop = $this->shop();
        $url = WhatsAppLink::forOrder($shop, $this->order($shop));

        $this->assertNotNull($url);
        $this->assertStringStartsWith('https://wa.me/96170123456?text=', $url);

        $text = rawurldecode(substr($url, strlen('https://wa.me/96170123456?text=')));

        $this->assertStringContainsString('ABC234', $text);
        $this->assertStringContainsString('Olive', $text);
        $this->assertStringContainsString('2 × House Bowl  $28.00', $text);
        $this->assertStringContainsString('1 × Daily Tart  $11.00', $text);
        $this->assertStringContainsString('Total: $39.00', $text);
    }

    public function test_a_note_is_passed_along(): void
    {
        $shop = $this->shop();
        $order = $this->order($shop);
        $order->update(['note' => 'No coriander']);

        $url = WhatsAppLink::forOrder($shop, $order->fresh()->load('items'));
        $text = rawurldecode((string) parse_url((string) $url, PHP_URL_QUERY));

        $this->assertStringContainsString('No coriander', $text);
    }

    public function test_the_currency_symbol_follows_the_restaurant(): void
    {
        $shop = $this->shop(['currency' => 'EUR']);
        $url = (string) WhatsAppLink::forOrder($shop, $this->order($shop));
        $text = rawurldecode($url);

        $this->assertStringContainsString('€39.00', $text);
    }
}
