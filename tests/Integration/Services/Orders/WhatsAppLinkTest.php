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

        $this->assertNull(WhatsAppLink::forOrder($shop, $this->order($shop), 'en'));
    }

    /** The message the link carries, decoded. */
    private function text(Restaurant $shop, Order $order, string $locale = 'en'): string
    {
        $url = (string) WhatsAppLink::forOrder($shop, $order, $locale);
        $this->assertStringStartsWith('https://wa.me/96170123456?text=', $url);

        return rawurldecode(substr($url, strlen('https://wa.me/96170123456?text=')));
    }

    public function test_the_message_is_laid_out_plainly_without_the_reference(): void
    {
        $shop = $this->shop();
        $order = $this->order($shop);
        $order->items->first()->update(['options' => [
            'variants' => [['name' => 'Size', 'choice' => 'Large', 'price' => '2.00'], ['name' => 'Spice', 'choice' => 'Hot', 'price' => '0.00']],
            'addons' => [['name' => 'Extra cheese', 'price' => '1.00']],
        ]]);
        $order->update(['note' => 'No coriander', 'fulfilment' => 'delivery', 'guest_name' => 'Rami', 'guest_phone' => '+96170123456', 'address' => 'Hamra street']);

        $this->assertSame(implode("\n", [
            '*New order · Olive*',
            '*Delivery*',
            '',
            'Name: Rami',
            'Phone: +96170123456',
            'Address: Hamra street',
            '',
            '*2 House Bowl*   $28',
            '• Large',
            '• Hot',
            '• Extra cheese',
            '',
            '*1 Daily Tart*   $11',
            '',
            '*Total: $39*',
            '',
            'Note: No coriander',
        ]), $this->text($shop, $order->fresh()->load('items')));
    }

    public function test_it_is_written_in_the_language_the_guest_had_the_menu_in(): void
    {
        $shop = $this->shop(['second_locale' => 'ar', 'name' => ['en' => 'Olive', 'ar' => 'زيتون']]);
        $order = $this->order($shop);
        // OrderPlacer named the lines in the guest's language.
        $order->items->first()->update(['name' => 'وعاء البيت', 'options' => [
            'variants' => [['name' => 'الحجم', 'choice' => 'كبير', 'price' => '2.00']],
            'addons' => [['name' => 'جبنة إضافية', 'price' => '1.00']],
        ]]);
        $order->update(['note' => 'بدون بصل', 'fulfilment' => 'pickup', 'guest_name' => 'رامي']);
        app()->setLocale('fr');

        $this->assertSame(implode("\n", [
            '*طلب جديد · زيتون*',
            '*استلام*',
            '',
            'الاسم: رامي',
            '',
            '*2 وعاء البيت*   $28',
            '• كبير',
            '• جبنة إضافية',
            '',
            '*1 Daily Tart*   $11',
            '',
            '*المجموع: $39*',
            '',
            'ملاحظة: بدون بصل',
        ]), $this->text($shop, $order->fresh()->load('items'), 'ar'));
        // The same order read in English is written in English.
        $this->assertStringStartsWith("*New order · Olive*\n*Pickup*\n", $this->text($shop, $order, 'en'));
    }

    /**
     * One line built from parts, as OrderPlacer keeps it.
     *
     * @param  list<string>  $choices
     */
    private function built(Restaurant $shop, array $choices, string $shape = 'split', ?string $size = null, array $options = []): string
    {
        $order = Order::factory()->create(['restaurant_id' => $shop->id, 'currency' => 'USD', 'total' => '14.00']);
        $share = $shape === 'split' ? count($choices) : 1;
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'name' => 'Build your pizza',
            'unit_price' => '14.00',
            'quantity' => 1,
            'line_total' => '14.00',
            'options' => [...$options, 'builds' => [[
                'name' => 'Your pizza',
                'shape' => $shape,
                'parts' => array_map(fn (string $choice): array => ['choice' => $choice, 'share' => $share], $choices),
                'price' => '14.00',
                ...($size === null ? [] : ['size' => $size]),
            ]]],
        ]);

        $text = $this->text($shop, $order->load('items'), $shop->main_locale);
        preg_match('/\n\n(\*1 .*?)\n\n\*/s', $text, $match);

        return $match[1];
    }

    public function test_a_built_dish_is_named_by_what_was_built_its_size_first(): void
    {
        $shop = $this->shop();

        $this->assertSame('*1 Large: Half Margherita, Half Pepperoni*   $14', $this->built($shop, ['Margherita', 'Pepperoni'], size: 'Large'));
        $this->assertSame('*1 Half Margherita, Half Pepperoni*   $14', $this->built($shop, ['Margherita', 'Pepperoni']));
    }

    public function test_thirds_and_quarters_are_said_in_words_and_the_same_choice_added_up(): void
    {
        $shop = $this->shop();

        $this->assertSame('*1 A third Margherita, A third Pepperoni, A third Hawaiian*   $14', $this->built($shop, ['Margherita', 'Pepperoni', 'Hawaiian']));
        $this->assertSame('*1 Two thirds Margherita, A third Pepperoni*   $14', $this->built($shop, ['Margherita', 'Pepperoni', 'Margherita']));
        $this->assertSame('*1 A quarter Margherita, A quarter Pepperoni, A quarter Hawaiian, A quarter Veggie*   $14', $this->built($shop, ['Margherita', 'Pepperoni', 'Hawaiian', 'Veggie']));
        // Two quarters of one pizza are a half.
        $this->assertSame('*1 Half Margherita, A quarter Pepperoni, A quarter Veggie*   $14', $this->built($shop, ['Margherita', 'Pepperoni', 'Margherita', 'Veggie']));
        $this->assertSame('*1 Three quarters Margherita, A quarter Pepperoni*   $14', $this->built($shop, ['Margherita', 'Margherita', 'Pepperoni', 'Margherita']));
        // One choice all over is just that pizza.
        $this->assertSame('*1 Large: Margherita*   $14', $this->built($shop, ['Margherita', 'Margherita'], size: 'Large'));
        // A share without a word keeps its number.
        $this->assertSame('*1 2/5 Margherita, 1/5 Pepperoni, 2/5 Veggie*   $14', $this->built($shop, ['Margherita', 'Pepperoni', 'Margherita', 'Veggie', 'Veggie']));
    }

    public function test_scoops_are_each_named_and_the_dishes_choices_go_below(): void
    {
        $shop = $this->shop();

        $this->assertSame(implode("\n", [
            '*1 Vanilla, Vanilla, Chocolate*   $14',
            '• Cone',
            '• Sprinkles',
        ]), $this->built($shop, ['Vanilla', 'Chocolate', 'Vanilla'], 'pieces', options: [
            'variants' => [['name' => 'Served in', 'choice' => 'Cone', 'price' => '0.00']],
            'addons' => [['name' => 'Sprinkles', 'price' => '0.50']],
        ]));
    }

    public function test_a_built_dish_reads_naturally_in_arabic(): void
    {
        $shop = $this->shop(['main_locale' => 'ar', 'name' => ['ar' => 'زيتون']]);

        $this->assertSame('*1 كبير: نصف مارغريتا، نصف بيبروني*   $14', $this->built($shop, ['مارغريتا', 'بيبروني'], size: 'كبير'));
        $this->assertSame('*1 ثلثا مارغريتا، ثلث بيبروني*   $14', $this->built($shop, ['مارغريتا', 'بيبروني', 'مارغريتا']));
        $this->assertSame('*1 ثلاثة أرباع مارغريتا، ربع بيبروني*   $14', $this->built($shop, ['مارغريتا', 'مارغريتا', 'بيبروني', 'مارغريتا']));
    }

    public function test_a_second_build_goes_below_in_the_same_words(): void
    {
        $shop = $this->shop();
        $order = Order::factory()->create(['restaurant_id' => $shop->id, 'currency' => 'USD', 'total' => '14.00']);
        $build = fn (string $size, array $choices): array => [
            'name' => 'Your pizza', 'shape' => 'split', 'price' => '7.00', 'size' => $size,
            'parts' => array_map(fn (string $choice): array => ['choice' => $choice, 'share' => count($choices)], $choices),
        ];
        OrderItem::factory()->create([
            'order_id' => $order->id, 'name' => 'Two pizzas', 'unit_price' => '14.00', 'quantity' => 1, 'line_total' => '14.00',
            'options' => ['builds' => [$build('Large', ['Margherita', 'Pepperoni']), $build('Small', ['Veggie'])]],
        ]);

        $this->assertStringContainsString(
            "\n\n*1 Large: Half Margherita, Half Pepperoni*   \$14\n• Small: Veggie\n\n",
            $this->text($shop, $order->load('items')),
        );
    }

    public function test_the_table_comes_first_named_once(): void
    {
        $shop = $this->shop();
        $order = $this->order($shop);

        $order->update(['table_name' => 'Table 5']);
        $this->assertStringStartsWith("*New order · Olive*\n*Table 5*\n\n", $this->text($shop, $order));

        // A name that does not say "table" gets the word in front.
        $order->update(['table_name' => 'Terrace']);
        $this->assertStringStartsWith("*New order · Olive*\n*Table: Terrace*\n\n", $this->text($shop, $order));
    }

    public function test_an_asterisk_in_a_name_cannot_break_the_bold(): void
    {
        $shop = $this->shop(['name' => ['en' => 'Pizza *Hub*']]);

        $this->assertStringStartsWith('*New order · Pizza Hub*', $this->text($shop, $this->order($shop)));
    }

    public function test_a_note_is_passed_along(): void
    {
        $shop = $this->shop();
        $order = $this->order($shop);
        $order->update(['note' => 'No coriander']);

        $url = WhatsAppLink::forOrder($shop, $order->fresh()->load('items'), 'en');
        $text = rawurldecode((string) parse_url((string) $url, PHP_URL_QUERY));

        $this->assertStringContainsString('No coriander', $text);
    }

    public function test_the_currency_symbol_follows_the_restaurant(): void
    {
        $shop = $this->shop(['currency' => 'EUR']);
        $url = (string) WhatsAppLink::forOrder($shop, $this->order($shop), 'en');
        $text = rawurldecode($url);

        $this->assertStringContainsString('€39', $text);
    }
}
