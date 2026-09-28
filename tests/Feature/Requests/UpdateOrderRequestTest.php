<?php

namespace Tests\Feature\Requests;

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** App\Http\Requests\UpdateOrderRequest, through PATCH /api/orders/{order}. */
class UpdateOrderRequestTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, array{0: string}> */
    public static function statuses(): array
    {
        return array_combine(
            array_column(OrderStatus::cases(), 'value'),
            array_map(fn (OrderStatus $status): array => [$status->value], OrderStatus::cases()),
        );
    }

    /** @return array<string, array{0: array<string, mixed>, 1: string}> */
    public static function badBodies(): array
    {
        return [
            'missing' => [[], 'The status field is required.'],
            'null' => [['status' => null], 'The status field is required.'],
            'blank' => [['status' => ''], 'The status field is required.'],
            'unknown' => [['status' => 'shipped'], 'The selected status is invalid.'],
            'wrong case' => [['status' => 'DONE'], 'The selected status is invalid.'],
            'an array' => [['status' => ['done']], 'The selected status is invalid.'],
        ];
    }

    #[DataProvider('statuses')]
    public function test_every_status_can_be_set(string $status): void
    {
        $shop = $this->owner();
        $order = Order::factory()->for($shop)->status($status === 'placed' ? OrderStatus::Done : OrderStatus::Placed)->create();

        $this->actingAs($shop->user)
            ->patchJson(route('api.orders.update', $order), ['status' => $status])
            ->assertOk()
            ->assertJsonPath('data.status', $status);

        $this->assertSame($status, $order->refresh()->status->value);
    }

    /** @param  array<string, mixed>  $body */
    #[DataProvider('badBodies')]
    public function test_anything_but_a_known_status_is_refused(array $body, string $message): void
    {
        $shop = $this->owner();
        $order = Order::factory()->for($shop)->create();

        $this->actingAs($shop->user)
            ->patchJson(route('api.orders.update', $order), $body)
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['status' => $message]);

        $this->assertSame(OrderStatus::Placed, $order->refresh()->status);
    }

    public function test_only_the_status_is_written(): void
    {
        $shop = $this->owner();
        $order = Order::factory()->for($shop)->create(['total' => '14.00', 'note' => 'No onions']);

        $this->actingAs($shop->user)
            ->patchJson(route('api.orders.update', $order), ['status' => 'done', 'total' => '0.00', 'note' => 'hacked'])
            ->assertOk();

        $order->refresh();
        $this->assertSame('14.00', (string) $order->total);
        $this->assertSame('No onions', $order->note);
    }

    public function test_a_user_without_a_restaurant_is_refused_before_validation(): void
    {
        $order = Order::factory()->create();

        $this->actingAs($this->userWithoutRestaurant())
            ->patchJson(route('api.orders.update', $order), ['status' => 'nonsense'])
            ->assertForbidden()
            ->assertExactJson(['message' => 'This action is unauthorized.', 'code' => 'forbidden']);
    }

    public function test_another_restaurants_order_is_forbidden(): void
    {
        $shop = $this->owner();
        $theirs = Order::factory()->create();

        $this->actingAs($shop->user)
            ->patchJson(route('api.orders.update', $theirs), ['status' => 'done'])
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');

        $this->assertSame(OrderStatus::Placed, $theirs->refresh()->status);
    }

    public function test_a_missing_order_is_a_404(): void
    {
        $this->actingAs($this->owner()->user)
            ->patchJson(route('api.orders.update', 999999), ['status' => 'done'])
            ->assertNotFound()
            ->assertJsonPath('code', 'not_found');
    }

    public function test_a_guest_gets_a_401(): void
    {
        $order = Order::factory()->create();

        $this->patchJson(route('api.orders.update', $order), ['status' => 'done'])
            ->assertUnauthorized();
    }
}
