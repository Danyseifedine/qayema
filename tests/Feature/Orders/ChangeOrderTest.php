<?php

namespace Tests\Feature\Orders;

use App\Enums\Feature;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishAddon;
use App\Models\DishVariant;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A guest changing an order they placed in the menu, from its tracking page:
 * until the restaurant accepts it, and the owner can tell it changed.
 */
class ChangeOrderTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private Restaurant $shop;

    private Dish $burger;

    private Dish $fries;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::Variants, Feature::Addons);
        $this->shop = $this->published(['slug' => 'olive', 'order_mode' => 'menu', 'timezone' => 'UTC']);
        $category = Category::factory()->create(['restaurant_id' => $this->shop->id]);
        $this->burger = Dish::factory()->create(['restaurant_id' => $this->shop->id, 'category_id' => $category->id, 'name' => ['en' => 'Burger'], 'price' => '8.00', 'is_available' => true]);
        $this->fries = Dish::factory()->create(['restaurant_id' => $this->shop->id, 'category_id' => $category->id, 'name' => ['en' => 'Fries'], 'price' => '3.00', 'is_available' => true]);

        $size = DishVariant::factory()->create(['dish_id' => $this->burger->id, 'name' => ['en' => 'Size']]);
        $size->options()->createMany([
            ['name' => ['en' => 'Small'], 'price' => '0.00', 'display_order' => 0],
            ['name' => ['en' => 'Large'], 'price' => '3.00', 'display_order' => 1],
        ]);
        DishAddon::factory()->create(['dish_id' => $this->burger->id, 'name' => ['en' => 'Bacon'], 'price' => '2.50']);
    }

    private function option(string $name): int
    {
        return $this->burger->variants()->first()->options()->get()->first(fn ($option) => $option->getTranslation('name', 'en') === $name)->id;
    }

    /** What the cart sends; a pickup, so no address. */
    private function cart(array $items, array $overrides = []): array
    {
        return array_merge([
            'items' => $items,
            'mode' => 'menu',
            'fulfilment' => 'pickup',
            'name' => 'Rami',
            'phone_country' => 'AE',
            'phone' => '050 123 4567',
            'note' => 'No onions',
            'client_token' => '1b4e28ba-2fa1-41d2-883f-0016d3cca427',
        ], $overrides);
    }

    /** What the cart sends to change an order: its version, and an id of its own. */
    private function change(array $items, array $overrides = []): array
    {
        return $this->cart($items, ['version' => 0, 'client_token' => 'cccccccc-2fa1-41d2-883f-0016d3cca427', ...$overrides]);
    }

    private function update(Order $order, array $body): TestResponse
    {
        return $this->putJson(route('public.order.update', [$this->shop->slug, $order->tracking_token]), $body);
    }

    private function place(): Order
    {
        $this->postJson(route('public.order', $this->shop->slug), $this->cart([
            ['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$this->option('Small')], 'addons' => [$this->burger->addons()->first()->id]],
        ]))->assertCreated();

        return Order::query()->firstOrFail();
    }

    public function test_the_cart_comes_back_as_it_was_ordered(): void
    {
        $order = $this->place();

        $this->getJson(route('public.order.cart', [$this->shop->slug, $order->tracking_token]))
            ->assertOk()
            ->assertExactJson(['data' => [
                'editable' => true,
                'reference' => $order->reference,
                'version' => 0,
                'update_url' => route('public.order.update', [$this->shop->slug, $order->tracking_token]),
                'lines' => [[
                    'dish' => (string) $this->burger->id,
                    'options' => [$this->option('Small')],
                    'addons' => [$this->burger->addons()->first()->id],
                    'qty' => 1,
                ]],
                'details' => [
                    'fulfilment' => 'pickup',
                    'name' => 'Rami',
                    // Back into the form as it was typed: the country and the rest.
                    'country' => 'AE',
                    'phone' => '501234567',
                    'address' => '',
                    'note' => 'No onions',
                ],
            ]]);
    }

    public function test_the_guest_changes_it_and_the_owner_can_tell(): void
    {
        $order = $this->place();
        $this->travelTo(Carbon::parse('2026-10-03 12:05', 'UTC'));

        $this->update($order, $this->change([
            ['dish_id' => $this->burger->id, 'quantity' => 2, 'options' => [$this->option('Large')]],
            ['dish_id' => $this->fries->id, 'quantity' => 1],
        ], ['note' => 'Extra napkins', 'client_token' => 'aaaaaaaa-2fa1-41d2-883f-0016d3cca427']))
            ->assertOk()
            ->assertJsonPath('data.reference', $order->reference)
            ->assertJsonPath('data.total', '25.00')
            ->assertJsonPath('data.tracking_url', $order->trackingUrl());

        $changed = $order->fresh()->load('items');
        $this->assertSame(1, Order::query()->count());
        $this->assertSame('25.00', (string) $changed->total);
        $this->assertSame(['Burger', 'Fries'], $changed->items->pluck('name')->all());
        $this->assertSame(['Large'], $changed->items[0]->picks());
        $this->assertSame('Extra napkins', $changed->note);
        $this->assertSame(1, $changed->guest_updates);
        $this->assertSame('12:05', $changed->guest_updated_at->format('H:i'));
        // The same order: its number, its link and the first press's id stay.
        $this->assertSame($order->tracking_token, $changed->tracking_token);
        $this->assertSame('1b4e28ba-2fa1-41d2-883f-0016d3cca427', $changed->client_token);

        $this->assertStringContainsString('You changed this order at 12:05.', (string) $this->getJson($order->trackingUrl())->json('data.html'));

        $this->actingAs($this->shop->user)->getJson(route('api.orders.pulse'))
            ->assertJsonPath('data.changed', '2026-10-03T12:05:00+00:00');
        $this->actingAs($this->shop->user)->getJson(route('api.orders.index'))
            ->assertJsonPath('data.0.guest_updates', 1)
            ->assertJsonPath('data.0.guest_updated_at', '2026-10-03T12:05:00+00:00');
    }

    /** Accepted, the kitchen may be at it: a change goes through a call. */
    public function test_once_accepted_it_can_no_longer_change(): void
    {
        foreach ([OrderStatus::Accepted, OrderStatus::Ready] as $status) {
            Order::query()->delete();
            $order = $this->place();
            $order->moveTo($status);

            $this->getJson(route('public.order.cart', [$this->shop->slug, $order->tracking_token]))
                ->assertJsonPath('data.editable', false);

            $this->update($order, $this->change([
                ['dish_id' => $this->fries->id, 'quantity' => 5],
            ]))
                ->assertStatus(409)
                ->assertJsonPath('message', 'The restaurant has already accepted your order, so it can no longer be changed. Call them if you need to.');

            $this->assertSame(['Burger'], $order->fresh()->items->pluck('name')->all());
            $this->assertSame(0, $order->fresh()->guest_updates);
            $this->assertStringNotContainsString('Change my order', (string) $this->getJson($order->trackingUrl())->json('data.html'));
        }
    }

    public function test_the_tracking_sheet_offers_the_change_while_it_can(): void
    {
        $order = $this->place();

        $html = (string) $this->getJson($order->trackingUrl())->json('data.html');
        $this->assertStringContainsString('Change my order', $html);
        $this->assertStringContainsString('data-edit-order="'.$order->tracking_token.'"', $html);
    }

    public function test_a_change_is_checked_like_a_new_order(): void
    {
        $order = $this->place();

        $this->update($order, $this->change([
            ['dish_id' => $this->burger->id, 'quantity' => 1],
        ]))->assertJsonValidationErrors(['items' => 'Choose a Size for Burger.']);
        $this->update($order, $this->change([
            ['dish_id' => $this->fries->id, 'quantity' => 1],
        ], ['phone' => '']))->assertJsonValidationErrors(['phone']);
        // A change says which version of the order it was made from.
        $this->update($order, $this->cart([
            ['dish_id' => $this->fries->id, 'quantity' => 1],
        ]))->assertJsonValidationErrors(['version']);

        $this->assertSame(['Burger'], $order->fresh()->items->pluck('name')->all());
    }

    public function test_only_its_own_link_changes_it(): void
    {
        $order = $this->place();

        $this->putJson(route('public.order.update', [$this->shop->slug, str_repeat('a', 40)]), $this->change([
            ['dish_id' => $this->fries->id, 'quantity' => 1],
        ]))->assertNotFound();
        $this->assertSame(0, $order->fresh()->guest_updates);
    }

    /** An answer lost on mobile data, then a second tap: added once. */
    public function test_the_same_change_sent_twice_is_made_once(): void
    {
        $order = $this->place();
        $body = $this->change([
            ['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$this->option('Small')], 'addons' => [$this->burger->addons()->first()->id]],
            ['dish_id' => $this->fries->id, 'quantity' => 1],
        ]);

        $this->update($order, $body)->assertOk()->assertJsonPath('data.total', '13.50');
        $this->update($order, $body)->assertOk()->assertJsonPath('data.total', '13.50');

        $this->assertSame(1, $order->fresh()->guest_updates);
        $this->assertSame(['Burger', 'Fries'], $order->fresh()->items->pluck('name')->all());
    }

    /** Another tab or phone changed it first: this one would undo that. */
    public function test_a_change_made_from_an_older_version_is_refused(): void
    {
        $order = $this->place();
        $this->update($order, $this->change([['dish_id' => $this->fries->id, 'quantity' => 1]]))->assertOk();

        $this->update($order, $this->change([['dish_id' => $this->fries->id, 'quantity' => 4]], ['client_token' => 'dddddddd-2fa1-41d2-883f-0016d3cca427']))
            ->assertStatus(409)
            ->assertJsonPath('message', 'Your order was changed from another page meanwhile. Open it to see it as it is now, then try again.');

        $this->assertSame(1, $order->fresh()->items->first()->quantity);
        $this->getJson(route('public.order.cart', [$this->shop->slug, $order->tracking_token]))->assertJsonPath('data.version', 1);
    }

    public function test_a_shared_location_stays_with_its_address_only(): void
    {
        $this->postJson(route('public.order', $this->shop->slug), $this->cart([['dish_id' => $this->fries->id, 'quantity' => 1]], [
            'fulfilment' => 'delivery', 'address' => 'Office, Hamra', 'latitude' => 33.89, 'longitude' => 35.48,
        ]))->assertCreated();
        $order = Order::query()->firstOrFail();
        $delivery = ['fulfilment' => 'delivery', 'address' => 'Office, Hamra'];

        // A dish added: the address and its spot stay.
        $this->update($order, $this->change([['dish_id' => $this->fries->id, 'quantity' => 2]], $delivery))->assertOk();
        $this->assertNotNull($order->fresh()->latitude);

        // Somewhere else now: the office's spot would send the driver there.
        $this->update($order, $this->change([['dish_id' => $this->fries->id, 'quantity' => 2]], [
            ...$delivery, 'address' => 'Home, Achrafieh', 'version' => 1, 'client_token' => 'eeeeeeee-2fa1-41d2-883f-0016d3cca427',
        ]))->assertOk();
        $this->assertNull($order->fresh()->latitude);
        $this->assertNull($order->fresh()->mapUrl());
    }

    /** Marked unavailable since: the change goes without it, and says so. */
    public function test_a_dish_no_longer_sold_is_named_when_it_leaves_the_order(): void
    {
        $order = $this->place();
        $this->burger->update(['is_available' => false]);

        $this->update($order, $this->change([
            ['dish_id' => $this->burger->id, 'quantity' => 1, 'options' => [$this->option('Small')]],
            ['dish_id' => $this->fries->id, 'quantity' => 1],
        ]))
            ->assertOk()
            ->assertJsonPath('data.unavailable', ['Burger'])
            ->assertJsonPath('data.total', '3.00');
    }
}
