<?php

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_five_states_and_no_more(): void
    {
        $this->assertSame(['placed', 'accepted', 'ready', 'done', 'cancelled'], array_column(OrderStatus::cases(), 'value'));
        $this->assertNull(OrderStatus::tryFrom('preparing'));
    }

    public function test_only_a_new_order_is_open_to_the_guest(): void
    {
        $open = array_filter(OrderStatus::cases(), fn (OrderStatus $status): bool => $status->isOpenToGuest());

        $this->assertSame([OrderStatus::Placed], array_values($open));
    }

    public function test_an_order_moves_on_or_is_called_off_never_back(): void
    {
        $this->assertTrue(OrderStatus::Placed->canMoveTo(OrderStatus::Accepted));
        $this->assertTrue(OrderStatus::Placed->canMoveTo(OrderStatus::Done));
        $this->assertTrue(OrderStatus::Accepted->canMoveTo(OrderStatus::Ready));
        $this->assertTrue(OrderStatus::Ready->canMoveTo(OrderStatus::Cancelled));

        $this->assertFalse(OrderStatus::Ready->canMoveTo(OrderStatus::Accepted));
        $this->assertFalse(OrderStatus::Accepted->canMoveTo(OrderStatus::Placed));
        $this->assertFalse(OrderStatus::Accepted->canMoveTo(OrderStatus::Accepted));

        foreach (OrderStatus::cases() as $next) {
            $this->assertFalse(OrderStatus::Done->canMoveTo($next));
            $this->assertFalse(OrderStatus::Cancelled->canMoveTo($next));
        }
    }
}
