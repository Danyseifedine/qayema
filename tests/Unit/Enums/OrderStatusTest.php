<?php

namespace Tests\Unit\Enums;

use App\Enums\OrderStatus;
use PHPUnit\Framework\TestCase;

class OrderStatusTest extends TestCase
{
    public function test_three_states_and_no_more(): void
    {
        $this->assertSame(['placed', 'done', 'cancelled'], array_column(OrderStatus::cases(), 'value'));
        $this->assertNull(OrderStatus::tryFrom('preparing'));
    }
}
