<?php

namespace Tests\Feature\Coins;

use App\Enums\CoinTransactionType;
use App\Exceptions\InsufficientCoins;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_user_starts_with_no_coins(): void
    {
        $this->assertSame(0, User::factory()->create()->wallet()->balance());
    }

    public function test_crediting_raises_the_balance_and_writes_the_ledger(): void
    {
        $user = User::factory()->create();

        $transaction = $user->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_1');

        $this->assertNotNull($transaction);
        $this->assertSame(500, $transaction->amount);
        $this->assertSame(500, $transaction->balance_after);
        $this->assertSame(500, $user->wallet()->balance());
        $this->assertSame(500, (int) $user->fresh()->coin_balance);
    }

    public function test_spending_lowers_the_balance_and_records_a_negative_amount(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_1');

        $transaction = $user->wallet()->debit(200, CoinTransactionType::Spend, 'template:7');

        $this->assertSame(-200, $transaction->amount);
        $this->assertSame(300, $transaction->balance_after);
        $this->assertSame(300, (int) $user->fresh()->coin_balance);
    }

    public function test_spending_more_than_the_balance_is_refused(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(100, CoinTransactionType::Purchase, 'txn_1');

        try {
            $user->wallet()->debit(250);
            $this->fail('Expected InsufficientCoins.');
        } catch (InsufficientCoins $exception) {
            $this->assertSame(250, $exception->needed);
            $this->assertSame(100, $exception->balance);
            $this->assertSame(150, $exception->shortfall());
        }

        $this->assertSame(100, (int) $user->fresh()->coin_balance, 'The balance is untouched.');
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_spending_the_exact_balance_is_allowed(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(300, CoinTransactionType::Purchase, 'txn_1');

        $user->wallet()->debit(300);

        $this->assertSame(0, (int) $user->fresh()->coin_balance);
    }

    public function test_crediting_the_same_reference_twice_only_counts_once(): void
    {
        $user = User::factory()->create();

        $first = $user->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_replay');
        $second = $user->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_replay');

        $this->assertNotNull($first);
        $this->assertNull($second, 'A replayed reference is ignored.');
        $this->assertSame(500, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseCount('coin_transactions', 1);
    }

    public function test_the_same_reference_under_a_different_type_is_allowed(): void
    {
        $user = User::factory()->create();

        $user->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_1');
        $user->wallet()->credit(500, CoinTransactionType::AdminGrant, 'txn_1');

        $this->assertSame(1000, (int) $user->fresh()->coin_balance);
    }

    public function test_admin_grants_without_a_reference_can_repeat(): void
    {
        $user = User::factory()->create();

        $user->wallet()->credit(100, CoinTransactionType::AdminGrant);
        $user->wallet()->credit(100, CoinTransactionType::AdminGrant);

        $this->assertSame(200, (int) $user->fresh()->coin_balance);
        $this->assertDatabaseCount('coin_transactions', 2);
    }

    public function test_the_balance_always_equals_the_sum_of_the_ledger(): void
    {
        $user = User::factory()->create();

        $user->wallet()->credit(1000, CoinTransactionType::Purchase, 'txn_1');
        $user->wallet()->debit(250, CoinTransactionType::Spend, 'template:1');
        $user->wallet()->debit(400, CoinTransactionType::Spend, 'template:2');
        $user->wallet()->credit(50, CoinTransactionType::AdminGrant);

        $ledgerSum = (int) $user->coinTransactions()->sum('amount');

        $this->assertSame(400, $ledgerSum);
        $this->assertSame($ledgerSum, (int) $user->fresh()->coin_balance);
    }

    public function test_history_returns_the_newest_rows_first(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_1');
        $user->wallet()->debit(100, CoinTransactionType::Spend, 'template:1');

        $history = $user->wallet()->history();

        $this->assertCount(2, $history);
        $this->assertSame(-100, $history->first()->amount);
    }

    public function test_a_credit_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::factory()->create()->wallet()->credit(0);
    }

    public function test_a_debit_must_be_positive(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        User::factory()->create()->wallet()->debit(-5);
    }

    public function test_one_users_coins_never_affect_another(): void
    {
        $spender = User::factory()->create();
        $bystander = User::factory()->create();

        $spender->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_1');

        $this->assertSame(500, (int) $spender->fresh()->coin_balance);
        $this->assertSame(0, (int) $bystander->fresh()->coin_balance);
    }
}
