<?php

namespace App\Services\Global;

use App\Enums\CoinTransactionType;
use App\Exceptions\InsufficientCoins;
use App\Models\CoinTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * The owner's coin balance.
 *
 * `coin_transactions` is the source of truth and is append-only;
 * `users.coin_balance` is a cached running total written inside the same
 * transaction as the ledger row, under a row lock, so the two can't drift and
 * two concurrent spends can't both pass the same balance check.
 */
class Wallet
{
    public function __construct(private readonly User $user) {}

    public static function for(User $user): self
    {
        return new self($user);
    }

    public function balance(): int
    {
        $balance = (int) User::query()->whereKey($this->user->getKey())->value('coin_balance');

        $this->user->setAttribute('coin_balance', $balance);

        return $balance;
    }

    /**
     * Add coins. Returns the ledger row, or null when `reference` has already
     * been credited under this type — which is what makes a re-delivered Paddle
     * webhook harmless.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function credit(
        int $amount,
        CoinTransactionType $type = CoinTransactionType::Purchase,
        ?string $reference = null,
        ?array $meta = null,
    ): ?CoinTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('A credit must be a positive number of coins.');
        }

        try {
            return $this->write($amount, $type, $reference, $meta);
        } catch (QueryException $exception) {
            // The unique (type, reference) index rejected a replay. Treat it as
            // already done rather than an error — the coins are there either way.
            if ($reference !== null && $this->alreadyRecorded($type, $reference)) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * Spend coins, refusing to go below zero.
     *
     * @param  array<string, mixed>|null  $meta
     *
     * @throws InsufficientCoins
     */
    public function debit(
        int $amount,
        CoinTransactionType $type = CoinTransactionType::Spend,
        ?string $reference = null,
        ?array $meta = null,
    ): CoinTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('A debit must be a positive number of coins.');
        }

        return $this->write(-$amount, $type, $reference, $meta);
    }

    /**
     * Take back coins that may already have been spent: debits as much as the
     * balance allows and records the remainder as `meta.shortfall`. Returns null
     * when `reference` was already reversed under this type, so a replayed
     * refund webhook does nothing.
     *
     * @param  array<string, mixed>|null  $meta
     */
    public function clawBack(
        int $amount,
        CoinTransactionType $type = CoinTransactionType::Refund,
        ?string $reference = null,
        ?array $meta = null,
    ): ?CoinTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('A clawback must be a positive number of coins.');
        }

        try {
            return DB::transaction(function () use ($amount, $type, $reference, $meta): CoinTransaction {
                /** @var User $user */
                $user = User::query()->whereKey($this->user->getKey())->lockForUpdate()->firstOrFail();

                $available = (int) $user->coin_balance;
                $recovered = min($amount, $available);
                $shortfall = $amount - $recovered;

                $balance = $available - $recovered;

                $transaction = $user->coinTransactions()->create([
                    'amount' => -$recovered,
                    'balance_after' => $balance,
                    'type' => $type,
                    'reference' => $reference,
                    'meta' => array_merge($meta ?? [], ['shortfall' => $shortfall]),
                ]);

                $user->forceFill(['coin_balance' => $balance])->save();
                $this->user->setAttribute('coin_balance', $balance);

                return $transaction;
            });
        } catch (QueryException $exception) {
            if ($reference !== null && $this->alreadyRecorded($type, $reference)) {
                return null;
            }

            throw $exception;
        }
    }

    /**
     * The ledger, newest first.
     *
     * @return Collection<int, CoinTransaction>
     */
    public function history(int $limit = 50): Collection
    {
        return $this->user->coinTransactions()
            ->latest('id')
            ->limit($limit)
            ->get();
    }

    /**
     * Write one ledger row and move the cached balance with it. The row lock is
     * what serializes concurrent writes: the balance a spend checks is the same
     * one it then decrements.
     *
     * @param  array<string, mixed>|null  $meta
     */
    private function write(int $delta, CoinTransactionType $type, ?string $reference, ?array $meta): CoinTransaction
    {
        return DB::transaction(function () use ($delta, $type, $reference, $meta): CoinTransaction {
            /** @var User $user */
            $user = User::query()->whereKey($this->user->getKey())->lockForUpdate()->firstOrFail();

            $balance = (int) $user->coin_balance + $delta;

            if ($balance < 0) {
                throw new InsufficientCoins(abs($delta), (int) $user->coin_balance);
            }

            $transaction = $user->coinTransactions()->create([
                'amount' => $delta,
                'balance_after' => $balance,
                'type' => $type,
                'reference' => $reference,
                'meta' => $meta,
            ]);

            $user->forceFill(['coin_balance' => $balance])->save();

            // Keep the caller's instance in step so a follow-up balance() read
            // doesn't report the pre-write number.
            $this->user->setAttribute('coin_balance', $balance);

            return $transaction;
        });
    }

    private function alreadyRecorded(CoinTransactionType $type, string $reference): bool
    {
        return CoinTransaction::query()
            ->where('type', $type)
            ->where('reference', $reference)
            ->exists();
    }
}
