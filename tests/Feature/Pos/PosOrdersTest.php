<?php

namespace Tests\Feature\Pos;

use App\Enums\Feature;
use App\Enums\OrderStatus;
use App\Jobs\SendOrderToPos;
use App\Models\Category;
use App\Models\Dish;
use App\Models\DishAddonGroup;
use App\Models\Order;
use App\Models\PosConnection;
use App\Models\Restaurant;
use App\Services\Pos\PosOrders;
use App\Services\Pos\PosSignature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\Support\SendsMenuToken;
use Tests\TestCase;

/**
 * Orders placed in the menu of a restaurant that sends them to its POS: the
 * order the POS is sent (tests/Fixtures/pos-contract/order-request.json),
 * what happens when it takes it, refuses it or does not answer, the steps
 * it reports back, and what the guest and the owner may still change.
 */
class PosOrdersTest extends TestCase
{
    use CreatesOwners, RefreshDatabase, SendsMenuToken;

    private Restaurant $shop;

    private Dish $hummus;

    private PosConnection $connection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Ordering, Feature::MenuOrdering, Feature::OrderTracking, Feature::Addons, Feature::PosConnection);
        $this->shop = $this->published(['slug' => 'olive', 'currency' => 'USD', 'order_mode' => 'menu']);
        $category = Category::factory()->for($this->shop)->create(['pos_id' => '12']);
        $this->hummus = Dish::factory()->for($this->shop)->create(['category_id' => $category->id, 'name' => ['en' => 'Hummus'], 'price' => '6.00', 'is_available' => true, 'pos_id' => '301']);
        $this->connection = PosConnection::factory()->for($this->shop)->create(['address' => 'https://pos.test', 'sends_orders' => true]);
    }

    /** @param  array<string, mixed>  $overrides */
    private function place(array $overrides = []): TestResponse
    {
        return $this->postJson(route('public.order', $this->shop->slug), array_merge([
            'items' => [['dish_id' => $this->hummus->id, 'quantity' => 2]],
            'mode' => 'menu',
            'fulfilment' => 'delivery',
            'name' => 'Rami',
            'phone_country' => 'LB',
            'phone' => '70 123 456',
            'address' => 'Hamra street',
            'latitude' => 33.89,
            'longitude' => 35.47,
            'note' => 'No onions',
            'client_token' => '1b4e28ba-2fa1-41d2-883f-0016d3cca427',
            'website' => '',
        ], $overrides));
    }

    private function posAnswers(int $status = 201, ?array $body = null): void
    {
        Http::fake(['pos.test/api/v1/menu/orders' => Http::response($body ?? json_decode((string) file_get_contents(base_path('tests/Fixtures/pos-contract/order-response.json')), true), $status)]);
    }

    public function test_an_order_placed_in_the_menu_is_sent_to_the_pos_in_the_shape_of_the_contract(): void
    {
        $this->posAnswers();

        $this->place()->assertCreated();

        $order = Order::query()->sole();
        $this->assertSame(Order::POS_SENT, $order->pos_status);
        $this->assertSame('W-0001', $order->pos_number);
        $this->assertSame(OrderStatus::Placed, $order->status);
        $contract = json_decode((string) file_get_contents(base_path('tests/Fixtures/pos-contract/order-request.json')), true);
        Http::assertSent(function ($request) use ($order, $contract): bool {
            $sent = $request->data();

            return $request->url() === 'https://pos.test/api/v1/menu/orders'
                && $request->hasHeader('Authorization', 'Bearer '.$this->connection->key)
                && $sent['reference'] === $order->pos_reference
                && $sent['lines'] === [['product_id' => 301, 'quantity' => 2, 'unit_price' => '6.00', 'option_ids' => [], 'name' => 'Hummus']]
                && $sent['guest']['location'] === '33.8900000,35.4700000'
                && array_values(array_diff(array_keys($contract), array_keys($sent))) === ['table'];
        });
    }

    public function test_grouped_add_ons_reach_the_pos_by_its_own_ids(): void
    {
        $this->posAnswers();
        $group = DishAddonGroup::factory()->create(['dish_id' => $this->hummus->id, 'max_select' => 2, 'pos_id' => '5']);
        $garlic = $group->addons()->create(['dish_id' => $this->hummus->id, 'name' => ['en' => 'Garlic'], 'price' => '0.50']);
        $garlic->forceFill(['pos_id' => '11'])->save();

        $this->place(['items' => [['dish_id' => $this->hummus->id, 'quantity' => 1, 'addons' => [$garlic->id]]]])->assertCreated();

        Http::assertSent(fn ($request): bool => $request->data()['lines'][0]['option_ids'] === [11] && $request->data()['lines'][0]['unit_price'] === '6.50');
    }

    public function test_nothing_is_sent_while_sending_is_off_or_the_menu_is_not_connected(): void
    {
        Http::fake();
        $this->connection->update(['sends_orders' => false]);

        $this->place()->assertCreated();
        $this->assertNull(Order::query()->sole()->pos_status);

        Http::assertNothingSent();
    }

    public function test_an_order_the_pos_refuses_stays_here_with_the_reason_and_can_be_sent_again(): void
    {
        $this->posAnswers(422, ['success' => false, 'message' => 'Some items in this order are no longer sold.', 'code' => 'lines_unavailable', 'errors' => [], 'data' => ['lines' => [['index' => 0, 'product_id' => '301', 'message' => '"Hummus" is no longer sold.']]]]);

        $this->place()->assertCreated();

        $order = Order::query()->sole();
        $this->assertSame(Order::POS_FAILED, $order->pos_status);
        $this->assertSame('Some items in this order are no longer sold. "Hummus" is no longer sold.', $order->pos_error);

        Bus::fake();
        $this->actingAs($this->shop->user)->postJson(route('api.orders.send-to-pos', $order))
            ->assertOk()
            ->assertJsonPath('data.pos.status', 'sending');
        Bus::assertDispatched(SendOrderToPos::class);
    }

    public function test_a_pos_that_does_not_answer_is_tried_again_later(): void
    {
        Http::fake(['pos.test/*' => Http::response('', 503)]);
        Bus::fake();

        $this->place()->assertCreated();
        $order = Order::query()->sole();
        $this->assertSame(Order::POS_SENDING, $order->pos_status);
        Bus::assertDispatched(SendOrderToPos::class);

        app()->call([new SendOrderToPos($order->id), 'handle']);

        $this->assertSame(Order::POS_SENDING, $order->fresh()->pos_status);
        $this->assertNotNull($order->fresh()->pos_error);
    }

    public function test_a_dish_the_pos_did_not_send_keeps_the_order_here(): void
    {
        Http::fake();
        $this->hummus->forceFill(['pos_id' => null])->save();
        $this->connection->delete();
        PosConnection::factory()->for($this->shop)->create(['address' => 'https://pos.test', 'sends_orders' => true]);

        $this->place()->assertCreated();

        $this->assertSame(Order::POS_FAILED, Order::query()->sole()->pos_status);
        Http::assertNothingSent();
    }

    private function report(array $news): TestResponse
    {
        $body = (string) json_encode($news);
        $timestamp = time();

        return $this->call('POST', route('api.pos.webhook'), [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
            'HTTP_X_POS_CONNECTION' => $this->connection->pos_uuid,
            'HTTP_X_POS_TIMESTAMP' => (string) $timestamp,
            'HTTP_X_POS_SIGNATURE' => PosSignature::sign($this->connection->signing_secret, $timestamp, $body),
        ], $body);
    }

    public function test_each_step_the_pos_reports_moves_the_order_and_a_cancel_keeps_its_reason(): void
    {
        $this->posAnswers();
        $this->place()->assertCreated();
        $order = Order::query()->sole();

        $this->report(['event' => 'order.status', 'reference' => $order->pos_reference, 'status' => 'accepted'])->assertAccepted();
        $this->assertSame(OrderStatus::Accepted, $order->fresh()->status);

        $this->report(['event' => 'order.status', 'reference' => $order->pos_reference, 'status' => 'ready'])->assertAccepted();
        $this->assertSame(OrderStatus::Ready, $order->fresh()->status);

        $news = json_decode((string) file_get_contents(base_path('tests/Fixtures/pos-contract/webhook-order-status.json')), true);
        $this->report([...$news, 'reference' => $order->pos_reference])->assertAccepted();
        $this->assertSame(OrderStatus::Cancelled, $order->fresh()->status);
        $this->assertSame('Out of hummus', $order->fresh()->cancel_reason);
    }

    public function test_a_step_back_or_for_an_unknown_order_changes_nothing(): void
    {
        $this->posAnswers();
        $this->place()->assertCreated();
        $order = Order::query()->sole();
        $order->moveTo(OrderStatus::Done);

        $this->report(['event' => 'order.status', 'reference' => $order->pos_reference, 'status' => 'accepted'])->assertAccepted();
        $this->report(['event' => 'order.status', 'reference' => 'qy_nobody', 'status' => 'cancelled'])->assertAccepted();

        $this->assertSame(OrderStatus::Done, $order->fresh()->status);
    }

    public function test_an_order_in_the_pos_is_changed_there_not_by_the_guest_or_the_owner(): void
    {
        $this->posAnswers();
        $response = $this->place()->assertCreated();
        $order = Order::query()->sole();

        $this->actingAs($this->shop->user)->patchJson(route('api.orders.update', $order), ['status' => 'accepted'])
            ->assertConflict()
            ->assertJsonPath('code', 'handled_in_pos');
        $this->assertSame(OrderStatus::Placed, $order->fresh()->status);

        $token = basename((string) parse_url((string) $response->json('data.tracking_url'), PHP_URL_PATH));
        $this->putJson(route('public.order.update', ['restaurant' => $this->shop->slug, 'token' => $token]), [
            'items' => [['dish_id' => $this->hummus->id, 'quantity' => 1]],
            'mode' => 'menu', 'fulfilment' => 'delivery', 'name' => 'Rami', 'phone_country' => 'LB', 'phone' => '70 123 456',
            'address' => 'Hamra street', 'version' => 0, 'client_token' => '2b4e28ba-2fa1-41d2-883f-0016d3cca427', 'website' => '',
        ])->assertConflict();
    }

    public function test_the_owner_switches_sending_on_and_off_and_sees_whether_orders_are_placed_in_the_menu(): void
    {
        $this->actingAs($this->shop->user)->patchJson(route('api.pos-connection.update'), ['sends_orders' => false])
            ->assertOk()
            ->assertJsonPath('data.sends_orders', false)
            ->assertJsonPath('data.orders_in_menu', true);

        $this->assertFalse($this->connection->fresh()->sends_orders);
        $this->assertFalse(app(PosOrders::class)->sends($this->shop->fresh(), Order::factory()->for($this->shop)->make(['channel' => 'menu'])));
    }
}
