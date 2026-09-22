<?php

namespace Tests\Feature\Coins;

use App\Enums\CoinTransactionType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_wallet_requires_authentication(): void
    {
        $this->getJson(route('api.wallet'))->assertUnauthorized();
    }

    public function test_the_wallet_returns_the_balance_and_ledger(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(500, CoinTransactionType::Purchase, 'txn_1');
        $user->wallet()->debit(120, CoinTransactionType::Spend, 'template:3');

        $this->actingAs($user)
            ->getJson(route('api.wallet'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'balance',
                    'transactions' => [['id', 'amount', 'balance_after', 'type', 'label', 'reference', 'created_at']],
                ],
            ])
            ->assertJsonPath('data.balance', 380)
            ->assertJsonCount(2, 'data.transactions')
            // Newest first: the spend.
            ->assertJsonPath('data.transactions.0.amount', -120)
            ->assertJsonPath('data.transactions.0.type', 'spend')
            ->assertJsonPath('data.transactions.1.amount', 500);
    }

    public function test_an_empty_wallet_reports_a_zero_balance(): void
    {
        $this->actingAs(User::factory()->create())
            ->getJson(route('api.wallet'))
            ->assertOk()
            ->assertJsonPath('data.balance', 0)
            ->assertJsonCount(0, 'data.transactions');
    }

    public function test_one_owner_never_sees_another_owners_ledger(): void
    {
        $mine = User::factory()->create();
        $theirs = User::factory()->create();

        $mine->wallet()->credit(100, CoinTransactionType::Purchase, 'txn_mine');
        $theirs->wallet()->credit(900, CoinTransactionType::Purchase, 'txn_theirs');

        $this->actingAs($mine)
            ->getJson(route('api.wallet'))
            ->assertOk()
            ->assertJsonPath('data.balance', 100)
            ->assertJsonCount(1, 'data.transactions')
            ->assertJsonPath('data.transactions.0.reference', 'txn_mine');
    }

    public function test_coin_packs_require_authentication(): void
    {
        $this->getJson(route('api.coin-packs'))->assertUnauthorized();
    }

    public function test_the_ledger_is_paginated_newest_first(): void
    {
        $user = User::factory()->create();
        for ($i = 1; $i <= 30; $i++) {
            $user->wallet()->credit(1, CoinTransactionType::AdminGrant, null, ['n' => $i]);
        }

        $first = $this->actingAs($user)->getJson(route('api.wallet'))->assertOk();
        $first->assertJsonCount(25, 'data.transactions')
            ->assertJsonPath('meta.total', 30)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('data.balance', 30);

        $second = $this->actingAs($user)->getJson(route('api.wallet', ['page' => 2]))->assertOk();
        $second->assertJsonCount(5, 'data.transactions')->assertJsonPath('meta.current_page', 2);

        // Newest first: page 1 starts at the 30th credit, page 2 ends at the 1st.
        $this->assertSame(30, $first->json('data.transactions.0.balance_after'));
        $this->assertSame(1, $second->json('data.transactions.4.balance_after'));
    }

    public function test_an_out_of_range_page_is_empty_not_an_error(): void
    {
        $user = User::factory()->create();
        $user->wallet()->credit(5, CoinTransactionType::AdminGrant);

        $this->actingAs($user)->getJson(route('api.wallet', ['page' => 99]))
            ->assertOk()
            ->assertJsonCount(0, 'data.transactions')
            ->assertJsonPath('data.balance', 5);
    }
}
