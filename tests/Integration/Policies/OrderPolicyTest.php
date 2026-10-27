<?php

namespace Tests\Integration\Policies;

use App\Models\Order;
use App\Policies\OrderPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class OrderPolicyTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_policy_is_registered_for_orders(): void
    {
        $this->assertInstanceOf(OrderPolicy::class, Gate::getPolicyFor(Order::class));
    }

    public function test_an_owner_may_move_and_delete_their_own_orders(): void
    {
        $shop = $this->owner();
        $order = Order::factory()->make(['restaurant_id' => $shop->id]);

        $this->assertTrue($shop->user->can('manage', $order));
        $this->assertTrue($shop->user->can('delete', $order));
    }

    public function test_an_owner_may_not_touch_another_restaurants_orders(): void
    {
        $mine = $this->owner();
        $theirs = Order::factory()->make(['restaurant_id' => $this->owner()->id]);

        $this->assertFalse($mine->user->can('manage', $theirs));
        $this->assertFalse($mine->user->can('delete', $theirs));
    }

    public function test_a_user_without_a_restaurant_may_do_nothing(): void
    {
        $order = Order::factory()->make(['restaurant_id' => $this->owner()->id]);

        $this->assertFalse($this->userWithoutRestaurant()->can('manage', $order));
    }

    /** An admin deletes orders on the restaurant's page in /admin, but never changes one. */
    public function test_an_admin_may_delete_a_restaurants_orders_in_admin_but_not_manage_them(): void
    {
        $order = Order::factory()->make(['restaurant_id' => $this->owner()->id]);

        $this->assertFalse($this->admin()->can('manage', $order));
        $this->assertTrue($this->admin()->can('delete', $order));
    }

    public function test_the_refusal_carries_the_owners_words(): void
    {
        $order = Order::factory()->make(['restaurant_id' => $this->owner()->id]);

        $response = (new OrderPolicy)->manage($this->owner()->user, $order);

        $this->assertTrue($response->denied());
        $this->assertSame('You do not have access to this.', $response->message());
    }
}
