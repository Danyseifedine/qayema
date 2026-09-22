<?php

namespace Tests\Feature\Coins;

use App\Listeners\CreditPurchasedCoins;
use App\Models\CoinPack;
use App\Models\User;
use App\Services\Global\PaddlePrices;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Transaction;
use Tests\TestCase;

/**
 * Buying coins: the checkout hand-off to Paddle, and the webhook that actually
 * credits them.
 */
class CoinPurchaseTest extends TestCase
{
    use RefreshDatabase;

    private const PRICE = 'pri_starter_pack';

    protected function setUp(): void
    {
        parent::setUp();

        config(['cashier.api_key' => 'test-key', 'cashier.sandbox' => true]);

        // Creating the Paddle customer is a side effect of checkout, not what
        // these tests are about, so it is faked for every case here.
        Http::fake([
            'https://*api.paddle.com/customers*' => Http::response([
                'data' => ['id' => 'ctm_test', 'email' => 'buyer@example.com'],
            ]),
        ]);

        PaddlePrices::flush();
    }

    private function pack(int $coins = 500): CoinPack
    {
        return CoinPack::factory()->withCoins($coins)->create([
            'slug' => 'starter',
            'paddle_price_id_sandbox' => self::PRICE,
        ]);
    }

    /** Drive the listener the way Cashier does on `transaction.completed`. */
    private function completeTransaction(User $user, string $paddleId, array $items): void
    {
        $transaction = new Transaction(['paddle_id' => $paddleId]);

        app(CreditPurchasedCoins::class)->handle(
            new TransactionCompleted($user, $transaction, ['data' => ['items' => $items]])
        );
    }

    /** @return array<int, array<string, mixed>> */
    private function line(string $priceId, int $quantity = 1): array
    {
        return [['price' => ['id' => $priceId], 'quantity' => $quantity]];
    }

    public function test_checkout_requires_authentication(): void
    {
        $this->postJson(route('api.checkout'), ['pack_id' => 1])->assertUnauthorized();
    }

    public function test_checkout_returns_the_paddle_payload_for_a_pack(): void
    {
        $pack = $this->pack(500);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.checkout'), ['pack_id' => $pack->id])
            ->assertOk()
            ->assertJsonPath('items.0.priceId', self::PRICE)
            ->assertJsonPath('items.0.quantity', 1)
            ->assertJsonPath('coins', 500);

        // The buyer must exist on Paddle for the webhook to map the payment back.
        $this->assertDatabaseHas('customers', ['billable_id' => $user->id]);
    }

    public function test_checkout_multiplies_coins_by_the_quantity(): void
    {
        $pack = $this->pack(500);

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.checkout'), ['pack_id' => $pack->id, 'quantity' => 3])
            ->assertOk()
            ->assertJsonPath('items.0.quantity', 3)
            ->assertJsonPath('coins', 1500);
    }

    public function test_checkout_rejects_an_unknown_pack(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson(route('api.checkout'), ['pack_id' => 999999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pack_id');
    }

    public function test_checkout_rejects_an_inactive_pack(): void
    {
        $pack = CoinPack::factory()->inactive()->create();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.checkout'), ['pack_id' => $pack->id])
            ->assertStatus(422)
            ->assertJsonValidationErrors('pack_id');
    }

    public function test_checkout_rejects_a_pack_with_no_price_in_this_environment(): void
    {
        $pack = CoinPack::factory()->unpriced()->create();

        // It passes validation (it is active) but is not sellable here.
        $this->actingAs(User::factory()->create())
            ->postJson(route('api.checkout'), ['pack_id' => $pack->id])
            ->assertNotFound();
    }

    public function test_checkout_rejects_an_absurd_quantity(): void
    {
        $pack = $this->pack();

        $this->actingAs(User::factory()->create())
            ->postJson(route('api.checkout'), ['pack_id' => $pack->id, 'quantity' => 999])
            ->assertStatus(422)
            ->assertJsonValidationErrors('quantity');
    }

    public function test_a_completed_transaction_credits_the_coins(): void
    {
        $this->pack(500);
        $user = User::factory()->create();

        $this->completeTransaction($user, 'txn_1', $this->line(self::PRICE));

        $this->assertSame(500, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $user->id,
            'amount' => 500,
            'type' => 'purchase',
            'reference' => 'txn_1',
        ]);
    }

    public function test_the_quantity_paid_for_multiplies_the_coins(): void
    {
        $this->pack(500);
        $user = User::factory()->create();

        $this->completeTransaction($user, 'txn_1', $this->line(self::PRICE, 3));

        $this->assertSame(1500, (int) $user->fresh()->coin_balance);
    }

    public function test_a_redelivered_webhook_does_not_credit_twice(): void
    {
        $this->pack(500);
        $user = User::factory()->create();

        $this->completeTransaction($user, 'txn_1', $this->line(self::PRICE));
        $this->completeTransaction($user, 'txn_1', $this->line(self::PRICE));

        $this->assertSame(500, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_separate_transactions_accumulate(): void
    {
        $this->pack(500);
        $user = User::factory()->create();

        $this->completeTransaction($user, 'txn_1', $this->line(self::PRICE));
        $this->completeTransaction($user, 'txn_2', $this->line(self::PRICE));

        $this->assertSame(1000, (int) $user->fresh()->coin_balance);
    }

    public function test_a_price_that_is_not_a_coin_pack_is_ignored(): void
    {
        $this->pack(500);
        $user = User::factory()->create();

        $this->completeTransaction($user, 'txn_1', $this->line('pri_something_else'));

        $this->assertSame(0, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseCount('coin_transactions', 0);
    }

    public function test_the_coins_credited_come_from_the_pack_not_the_payload(): void
    {
        // The row says 500 — a payload claiming otherwise must not matter.
        $this->pack(500);
        $user = User::factory()->create();

        $this->completeTransaction($user, 'txn_1', [
            ['price' => ['id' => self::PRICE], 'quantity' => 1, 'coins' => 999999],
        ]);

        $this->assertSame(500, (int) $user->fresh()->coin_balance);
    }

    public function test_coin_packs_are_listed_with_live_paddle_amounts(): void
    {
        $this->pack(500);

        Http::fake([
            'https://*api.paddle.com/prices*' => Http::response([
                'data' => [[
                    'id' => self::PRICE,
                    'unit_price' => ['amount' => '1000', 'currency_code' => 'USD'],
                ]],
            ]),
        ]);
        PaddlePrices::flush();

        $this->actingAs(User::factory()->create())
            ->getJson(route('api.coin-packs'))
            ->assertOk()
            ->assertJsonPath('data.0.coins', 500)
            ->assertJsonPath('data.0.amount', 1000)
            ->assertJsonPath('data.0.currency', 'USD');
    }

    public function test_coin_packs_degrade_to_a_null_amount_when_paddle_is_unreachable(): void
    {
        $this->pack(500);

        Http::fake(['https://*api.paddle.com/*' => Http::response([], 500)]);
        PaddlePrices::flush();

        $this->actingAs(User::factory()->create())
            ->getJson(route('api.coin-packs'))
            ->assertOk()
            ->assertJsonPath('data.0.amount', null);
    }

    public function test_unsellable_packs_are_not_listed(): void
    {
        $this->pack(500);
        CoinPack::factory()->inactive()->create(['slug' => 'hidden']);
        CoinPack::factory()->unpriced()->create(['slug' => 'unpriced']);

        Http::fake(['https://*api.paddle.com/prices*' => Http::response(['data' => []])]);
        PaddlePrices::flush();

        $slugs = array_column(
            $this->actingAs(User::factory()->create())->getJson(route('api.coin-packs'))->json('data'),
            'slug'
        );

        $this->assertSame(['starter'], $slugs);
    }
}
