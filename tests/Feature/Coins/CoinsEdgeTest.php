<?php

namespace Tests\Feature\Coins;

use App\Models\CoinPack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\Concerns\FakesPaddle;
use Tests\TestCase;

class CoinsEdgeTest extends TestCase
{
    use CreatesOwners, FakesPaddle, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->fakePaddle();
    }

    public function test_checkout_quantity_boundaries(): void
    {
        $pack = $this->coinPack(500);
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('api.checkout'), ['pack_id' => $pack->id, 'quantity' => 0])->assertStatus(422)->assertJsonValidationErrors('quantity');
        $this->actingAs($user)->postJson(route('api.checkout'), ['pack_id' => $pack->id, 'quantity' => 21])->assertStatus(422)->assertJsonValidationErrors('quantity');
        $this->actingAs($user)->postJson(route('api.checkout'), ['pack_id' => $pack->id, 'quantity' => 'two'])->assertStatus(422)->assertJsonValidationErrors('quantity');
        $this->actingAs($user)->postJson(route('api.checkout'), ['pack_id' => $pack->id, 'quantity' => 20])->assertOk()->assertJsonPath('coins', 10000);
    }

    public function test_checkout_works_without_a_restaurant_yet(): void
    {
        // Coins belong to the user; buying them before onboarding finishes is fine.
        $pack = $this->coinPack(500);

        $this->actingAs($this->userWithoutRestaurant())->postJson(route('api.checkout'), ['pack_id' => $pack->id])->assertOk();
    }

    public function test_a_pack_taken_off_sale_after_checkout_still_credits_on_the_webhook(): void
    {
        $pack = $this->coinPack(500, 'pri_late');
        $user = User::factory()->create();
        $pack->update(['is_active' => false]);

        $this->completePaddleTransaction($user, 'txn_late', 'pri_late');

        $this->assertSame(500, (int) $user->fresh()->coin_balance, 'They paid; they get the coins.');
    }

    public function test_a_webhook_with_two_different_packs_credits_both(): void
    {
        $small = $this->coinPack(100, 'pri_small');
        $big = CoinPack::factory()->withCoins(1000)->create(['slug' => 'big', 'paddle_price_id_sandbox' => 'pri_big']);
        $user = User::factory()->create();
        $transaction = $user->transactions()->create(['paddle_id' => 'txn_multi', 'paddle_subscription_id' => null, 'invoice_number' => null, 'status' => 'completed', 'total' => '3000', 'tax' => '0', 'currency' => 'USD', 'billed_at' => now()]);

        app(\App\Listeners\CreditPurchasedCoins::class)->handle(new \Laravel\Paddle\Events\TransactionCompleted($user, $transaction, [
            'data' => ['items' => [['price' => ['id' => 'pri_small'], 'quantity' => 2], ['price' => ['id' => 'pri_big'], 'quantity' => 1]]],
        ]));

        $this->assertSame(1200, (int) $user->fresh()->coin_balance);
        $this->assertSame(1, $user->coinTransactions()->count(), 'One ledger row per transaction, with the breakdown in meta.');
        $this->assertCount(2, $user->coinTransactions()->first()->meta['packs']);
    }

    public function test_a_webhook_for_a_transaction_with_no_pack_items_credits_nothing(): void
    {
        $user = User::factory()->create();

        $this->completePaddleTransaction($user, 'txn_none', 'pri_not_a_pack');

        $this->assertSame(0, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseCount('coin_transactions', 0);
    }

    public function test_the_ledger_shows_a_purchase_a_spend_and_a_refund_with_labels(): void
    {
        $owner = $this->owner();
        $this->coinPack(1000, 'pri_1000');
        $this->completePaddleTransaction($owner->user, 'txn_1', 'pri_1000');
        $template = \App\Models\Template::factory()->paid(300)->create();
        $this->actingAs($owner->user)->postJson(route('api.templates.unlock'), ['template_id' => $template->id])->assertOk();
        $this->refundPaddleTransaction('txn_1');

        $rows = collect($this->actingAs($owner->user)->getJson(route('api.wallet'))->assertOk()->json('data.transactions'));

        $this->assertSame(['refund', 'spend', 'purchase'], $rows->pluck('type')->all());
        $this->assertSame([-700, -300, 1000], $rows->pluck('amount')->all());
        $this->assertSame(0, $this->actingAs($owner->user)->getJson(route('api.wallet'))->json('data.balance'));
        $this->assertNotSame('coins.refund', $rows[0]['label']);
    }

    public function test_the_invoice_link_is_only_for_the_owners_own_transaction(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();
        $transaction = $theirs->transactions()->create(['paddle_id' => 'txn_t', 'paddle_subscription_id' => null, 'invoice_number' => 'INV-9', 'status' => 'completed', 'total' => '1', 'tax' => '0', 'currency' => 'USD', 'billed_at' => now()]);

        $this->actingAs($mine)->getJson(route('api.purchases.invoice', $transaction))->assertNotFound();
    }

    public function test_coin_packs_are_listed_in_sort_order(): void
    {
        $this->coinPack(500, 'pri_b')->update(['sort_order' => 2, 'slug' => 'b']);
        CoinPack::factory()->withCoins(100)->create(['slug' => 'a', 'sort_order' => 1, 'paddle_price_id_sandbox' => 'pri_a']);
        \Illuminate\Support\Facades\Http::fake(['https://*api.paddle.com/prices*' => \Illuminate\Support\Facades\Http::response(['data' => []])]);
        \App\Services\Global\PaddlePrices::flush();

        $slugs = array_column($this->actingAs(User::factory()->create())->getJson(route('api.coin-packs'))->json('data'), 'slug');

        $this->assertSame(['a', 'b'], $slugs);
    }
}
