<?php

namespace Tests\Feature\Requests;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** App\Http\Requests\IndexOrdersRequest, through GET /api/orders. */
class IndexOrdersRequestTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /**
     * One order placed in the menu in each status, in the order of
     * OrderStatus::cases(); the Orders page lists only those.
     */
    private function oneOfEach(Restaurant $shop): void
    {
        foreach (OrderStatus::cases() as $status) {
            Order::factory()->for($shop)->inMenu()->status($status)->create(['reference' => strtoupper($status->value)]);
        }
    }

    public function test_without_a_status_every_order_is_listed(): void
    {
        $shop = $this->owner();
        $this->oneOfEach($shop);

        $response = $this->actingAs($shop->user)
            ->getJson(route('api.orders.index'))
            ->assertOk()
            ->assertJsonPath('meta.open', 1);

        $this->assertEqualsCanonicalizing(array_column(OrderStatus::cases(), 'value'), array_column($response->json('data'), 'status'));
    }

    public function test_a_blank_status_is_the_same_as_none(): void
    {
        $shop = $this->owner();
        $this->oneOfEach($shop);

        $this->actingAs($shop->user)
            ->getJson(route('api.orders.index').'?status=')
            ->assertOk()
            ->assertJsonCount(count(OrderStatus::cases()), 'data');
    }

    public function test_each_status_filters_the_list(): void
    {
        $shop = $this->owner();
        $this->oneOfEach($shop);

        foreach (OrderStatus::cases() as $status) {
            $this->actingAs($shop->user)
                ->getJson(route('api.orders.index', ['status' => $status->value]))
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.status', $status->value)
                ->assertJsonPath('data.0.reference', strtoupper($status->value))
                ->assertJsonPath('meta.open', 1);
        }
    }

    public function test_an_unknown_status_is_refused(): void
    {
        $shop = $this->owner();

        $this->actingAs($shop->user)
            ->getJson(route('api.orders.index', ['status' => 'shipped']))
            ->assertUnprocessable()
            ->assertJsonPath('code', 'validation_failed')
            ->assertJsonValidationErrors(['status' => 'The selected status is invalid.']);
    }

    public function test_a_status_list_is_refused(): void
    {
        $shop = $this->owner();

        $this->actingAs($shop->user)
            ->getJson(route('api.orders.index', ['status' => ['placed', 'done']]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_a_user_without_a_restaurant_is_refused_before_validation(): void
    {
        $this->actingAs($this->userWithoutRestaurant())
            ->getJson(route('api.orders.index', ['status' => 'nonsense']))
            ->assertForbidden()
            ->assertExactJson(['message' => 'This action is unauthorized.', 'code' => 'forbidden']);
    }
}
