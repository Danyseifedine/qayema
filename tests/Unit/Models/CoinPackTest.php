<?php

namespace Tests\Unit\Models;

use App\Models\CoinPack;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CoinPackTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_price_id_follows_the_paddle_environment(): void
    {
        $pack = new CoinPack(['paddle_price_id_sandbox' => 'pri_sandbox', 'paddle_price_id_production' => 'pri_live', 'is_active' => true]);

        config(['cashier.sandbox' => true]);
        $this->assertSame('pri_sandbox', $pack->paddlePriceId());

        config(['cashier.sandbox' => false]);
        $this->assertSame('pri_live', $pack->paddlePriceId());
    }

    public function test_an_empty_price_id_counts_as_missing(): void
    {
        config(['cashier.sandbox' => true]);

        $this->assertNull((new CoinPack(['paddle_price_id_sandbox' => '', 'is_active' => true]))->paddlePriceId());
        $this->assertNull((new CoinPack(['paddle_price_id_sandbox' => null, 'is_active' => true]))->paddlePriceId());
    }

    public function test_sellable_needs_both_active_and_a_price_for_this_environment(): void
    {
        config(['cashier.sandbox' => true]);

        $this->assertTrue((new CoinPack(['paddle_price_id_sandbox' => 'pri_x', 'is_active' => true]))->isSellable());
        $this->assertFalse((new CoinPack(['paddle_price_id_sandbox' => 'pri_x', 'is_active' => false]))->isSellable());
        $this->assertFalse((new CoinPack(['paddle_price_id_production' => 'pri_x', 'is_active' => true]))->isSellable(), 'Live id does not sell in sandbox.');
    }

    public function test_the_sellable_scope_matches_the_model_rule(): void
    {
        config(['cashier.sandbox' => true]);
        $sellable = CoinPack::factory()->create(['slug' => 'ok']);
        CoinPack::factory()->inactive()->create(['slug' => 'off']);
        CoinPack::factory()->unpriced()->create(['slug' => 'unpriced']);
        CoinPack::factory()->create(['slug' => 'live-only', 'paddle_price_id_sandbox' => null, 'paddle_price_id_production' => 'pri_live']);

        $this->assertSame([$sellable->id], CoinPack::query()->sellable()->pluck('id')->all());
    }

    public function test_a_pack_is_found_by_the_price_that_was_paid(): void
    {
        config(['cashier.sandbox' => true]);
        $pack = CoinPack::factory()->create(['paddle_price_id_sandbox' => 'pri_paid']);

        $this->assertTrue($pack->is(CoinPack::findByPaddlePriceId('pri_paid')));
        $this->assertNull(CoinPack::findByPaddlePriceId('pri_unknown'));
    }
}
