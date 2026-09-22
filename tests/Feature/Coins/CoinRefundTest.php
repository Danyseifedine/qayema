<?php

namespace Tests\Feature\Coins;

use App\Enums\CoinTransactionType;
use App\Listeners\ReverseRefundedCoins;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Events\WebhookReceived;
use Tests\TestCase;

/**
 * A refunded coin purchase takes the coins back. Paddle reports refunds as
 * adjustments against the transaction, never as a status change.
 */
class CoinRefundTest extends TestCase
{
    use RefreshDatabase;

    private const TXN = 'txn_refund_me';

    /** A user who bought 1000 coins in one Paddle transaction. */
    private function buyer(): User
    {
        $user = User::factory()->create();

        $user->transactions()->create([
            'paddle_id' => self::TXN,
            'paddle_subscription_id' => null,
            'invoice_number' => 'INV-1',
            'status' => 'completed',
            'total' => '2000',
            'tax' => '0',
            'currency' => 'USD',
            'billed_at' => now(),
        ]);

        $user->wallet()->credit(1000, CoinTransactionType::Purchase, self::TXN);

        return $user;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     */
    private function adjustment(string $id, string $status = 'approved', string $action = 'refund', array $items = [['type' => 'full']], ?string $total = null, string $event = 'adjustment.updated', string $transaction = self::TXN): void
    {
        app(ReverseRefundedCoins::class)->handle(new WebhookReceived([
            'event_type' => $event,
            'data' => [
                'id' => $id,
                'action' => $action,
                'status' => $status,
                'transaction_id' => $transaction,
                'items' => $items,
                'totals' => ['total' => $total ?? '2000'],
            ],
        ]));
    }

    public function test_a_full_refund_takes_every_coin_back(): void
    {
        $user = $this->buyer();

        $this->adjustment('adj_1');

        $this->assertSame(0, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseHas('coin_transactions', [
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => -1000,
            'reference' => 'adj_1',
        ]);
    }

    public function test_a_partial_refund_takes_the_same_share_of_coins(): void
    {
        $user = $this->buyer();

        // Half the money back → half the coins back.
        $this->adjustment('adj_1', items: [['type' => 'partial']], total: '1000');

        $this->assertSame(500, (int) $user->fresh()->coin_balance);
    }

    public function test_coins_already_spent_cannot_be_recovered_but_are_flagged(): void
    {
        Log::spy();
        $user = $this->buyer();
        $user->wallet()->debit(800, CoinTransactionType::Spend, 'template:1');

        $this->adjustment('adj_1');

        $ledger = $user->coinTransactions()->where('type', 'refund')->first();

        $this->assertSame(0, (int) $user->fresh()->coin_balance, 'Floors at zero, never negative.');
        $this->assertSame(-200, $ledger->amount, 'Only what was left could be taken.');
        $this->assertSame(800, $ledger->meta['shortfall']);
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_a_replayed_refund_webhook_is_ignored(): void
    {
        $user = $this->buyer();

        $this->adjustment('adj_1');
        $this->adjustment('adj_1');

        $this->assertSame(0, (int) $user->fresh()->coin_balance);
        $this->assertSame(1, $user->coinTransactions()->where('type', 'refund')->count());
    }

    public function test_a_pending_refund_does_nothing_yet(): void
    {
        $user = $this->buyer();

        $this->adjustment('adj_1', status: 'pending_approval');

        $this->assertSame(1000, (int) $user->fresh()->coin_balance);
    }

    public function test_a_non_refund_adjustment_is_ignored(): void
    {
        $user = $this->buyer();

        $this->adjustment('adj_1', action: 'credit');

        $this->assertSame(1000, (int) $user->fresh()->coin_balance);
    }

    public function test_an_unrelated_webhook_event_is_ignored(): void
    {
        $user = $this->buyer();

        $this->adjustment('adj_1', event: 'transaction.updated');

        $this->assertSame(1000, (int) $user->fresh()->coin_balance);
    }

    public function test_a_refund_for_an_unknown_transaction_is_ignored(): void
    {
        $user = $this->buyer();

        $this->adjustment('adj_1', transaction: 'txn_someone_else');

        $this->assertSame(1000, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_a_refund_for_a_transaction_that_never_credited_coins_is_ignored(): void
    {
        $user = User::factory()->create();
        $user->transactions()->create([
            'paddle_id' => 'txn_no_coins',
            'paddle_subscription_id' => null,
            'invoice_number' => null,
            'status' => 'completed',
            'total' => '500',
            'tax' => '0',
            'currency' => 'USD',
            'billed_at' => now(),
        ]);

        $this->adjustment('adj_1', transaction: 'txn_no_coins');

        $this->assertDatabaseCount('coin_transactions', 0);
    }

    public function test_the_refunded_user_can_still_be_credited_afterwards(): void
    {
        $user = $this->buyer();
        $this->adjustment('adj_1');

        $user->wallet()->credit(300, CoinTransactionType::AdminGrant);

        $this->assertSame(300, (int) $user->fresh()->coin_balance);
    }
}
