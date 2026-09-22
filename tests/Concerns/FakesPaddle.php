<?php

namespace Tests\Concerns;

use App\Listeners\CreditPurchasedCoins;
use App\Listeners\ReverseRefundedCoins;
use App\Models\CoinPack;
use App\Models\User;
use App\Services\Global\PaddlePrices;
use Illuminate\Support\Facades\Http;
use Laravel\Paddle\Events\TransactionCompleted;
use Laravel\Paddle\Events\WebhookReceived;
use Laravel\Paddle\Transaction;

/**
 * Paddle without the network: an API key, a customers endpoint that always
 * succeeds, and helpers that drive the two webhook listeners the way Cashier
 * would.
 */
trait FakesPaddle
{
    protected function fakePaddle(): void
    {
        config(['cashier.api_key' => 'test-key', 'cashier.sandbox' => true]);

        Http::fake([
            'https://*api.paddle.com/customers*' => Http::response([
                'data' => ['id' => 'ctm_test', 'email' => 'buyer@example.com'],
            ]),
        ]);

        PaddlePrices::flush();
    }

    /** A sellable pack with a sandbox price id. */
    protected function coinPack(int $coins = 500, string $priceId = 'pri_test_pack'): CoinPack
    {
        return CoinPack::factory()->withCoins($coins)->create([
            'slug' => 'pack-'.$coins,
            'paddle_price_id_sandbox' => $priceId,
        ]);
    }

    /** Paddle's `prices` listing, so the catalog shows live amounts. */
    protected function fakePaddlePrice(string $priceId, string $amountCents, string $currency = 'USD'): void
    {
        Http::fake([
            'https://*api.paddle.com/prices/*' => Http::response(['data' => []]),
            'https://*api.paddle.com/prices*' => Http::response([
                'data' => [[
                    'id' => $priceId,
                    'unit_price' => ['amount' => $amountCents, 'currency_code' => $currency],
                ]],
            ]),
        ]);

        PaddlePrices::flush();
    }

    /** What Cashier does when `transaction.completed` arrives for `$user`. */
    protected function completePaddleTransaction(User $user, string $transactionId, string $priceId, int $quantity = 1, string $total = '1000'): Transaction
    {
        $transaction = $user->transactions()->firstOrCreate(['paddle_id' => $transactionId], [
            'paddle_subscription_id' => null,
            'invoice_number' => 'INV-'.strtoupper(substr(md5($transactionId), 0, 6)),
            'status' => 'completed',
            'total' => $total,
            'tax' => '0',
            'currency' => 'USD',
            'billed_at' => now(),
        ]);

        app(CreditPurchasedCoins::class)->handle(new TransactionCompleted($user, $transaction, [
            'data' => ['items' => [['price' => ['id' => $priceId], 'quantity' => $quantity]]],
        ]));

        return $transaction;
    }

    /** An approved refund adjustment against a transaction. */
    protected function refundPaddleTransaction(string $transactionId, string $adjustmentId = 'adj_1', bool $full = true, string $total = '1000'): void
    {
        app(ReverseRefundedCoins::class)->handle(new WebhookReceived([
            'event_type' => 'adjustment.updated',
            'data' => [
                'id' => $adjustmentId,
                'action' => 'refund',
                'status' => 'approved',
                'transaction_id' => $transactionId,
                'items' => [['type' => $full ? 'full' : 'partial']],
                'totals' => ['total' => $total],
            ],
        ]));
    }
}
