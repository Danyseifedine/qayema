<?php

namespace Tests\Unit\Enums;

use App\Enums\OfferDiscount;
use PHPUnit\Framework\TestCase;

/** A discount worked in whole cents, half up, never below nothing (twin of menu-offers.js). */
class OfferDiscountTest extends TestCase
{
    public function test_a_share_off_rounds_half_up_to_the_cent(): void
    {
        $this->assertSame(1000, OfferDiscount::Percent->apply(1250, 20));
        // 999 x 0.85 = 849.15
        $this->assertSame(849, OfferDiscount::Percent->apply(999, 15));
        // 1 x 0.5 = 0.5
        $this->assertSame(1, OfferDiscount::Percent->apply(1, 50));
    }

    public function test_an_amount_off_never_goes_below_nothing(): void
    {
        $this->assertSame(1050, OfferDiscount::Amount->apply(1250, 2));
        $this->assertSame(0, OfferDiscount::Amount->apply(300, 5));
    }

    public function test_none_leaves_the_price(): void
    {
        $this->assertSame(1250, OfferDiscount::None->apply(1250, 30));
    }

    public function test_a_pair_deal_takes_its_share_off_the_second_dish_up_to_all_of_it(): void
    {
        $this->assertSame(625, OfferDiscount::Second->apply(1250, 50));
        $this->assertSame(0, OfferDiscount::Second->apply(1250, 100));
        $this->assertTrue(OfferDiscount::Second->isPair());
        $this->assertFalse(OfferDiscount::Percent->isPair());
    }
}
