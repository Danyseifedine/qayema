<?php

namespace App\Listeners;

use App\Enums\CoinTransactionType;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\Log;
use Laravel\Paddle\Cashier;
use Laravel\Paddle\Events\WebhookReceived;

/**
 * Claws coins back when a coin-pack purchase is refunded.
 *
 * In Paddle Billing a refund never changes the transaction's status — it
 * arrives as an `adjustment.*` event pointing at the transaction. Cashier
 * doesn't handle adjustments, so this listens to the raw webhook instead.
 *
 * The clawback is proportional to the refunded amount, floors at zero (the
 * balance column is unsigned and the coins may already be spent), and is
 * idempotent on the adjustment id.
 */
class ReverseRefundedCoins
{
    public function handle(WebhookReceived $event): void
    {
        $payload = $event->payload;

        if (! in_array($payload['event_type'] ?? null, ['adjustment.created', 'adjustment.updated'], true)) {
            return;
        }

        $data = $payload['data'] ?? [];

        if (($data['action'] ?? null) !== 'refund' || ($data['status'] ?? null) !== 'approved') {
            return;
        }

        $adjustmentId = $data['id'] ?? null;
        $transactionId = $data['transaction_id'] ?? null;

        if ($adjustmentId === null || $transactionId === null) {
            return;
        }

        $transaction = Cashier::$transactionModel::where('paddle_id', $transactionId)->first();
        $user = $transaction?->billable;

        if ($transaction === null || $user === null) {
            return;
        }

        // What this transaction originally credited, from our own ledger.
        $credit = CoinTransaction::query()
            ->where('user_id', $user->getKey())
            ->where('type', CoinTransactionType::Purchase)
            ->where('reference', $transactionId)
            ->first();

        if ($credit === null || $credit->amount < 1) {
            return;
        }

        $coins = $this->coinsToReverse($credit->amount, $data, (string) $transaction->total);

        if ($coins < 1) {
            return;
        }

        $result = $user->wallet()->clawBack(
            $coins,
            CoinTransactionType::Refund,
            $adjustmentId,
            ['transaction' => $transactionId, 'requested' => $coins],
        );

        if ($result !== null && ($result->meta['shortfall'] ?? 0) > 0) {
            Log::warning('Refund clawback could not recover every coin — some were already spent.', [
                'user_id' => $user->getKey(),
                'adjustment' => $adjustmentId,
                'requested' => $coins,
                'recovered' => abs($result->amount),
            ]);
        }
    }

    /**
     * Full refunds reverse the whole credit; partial ones reverse the same
     * share of the coins as the share of money returned.
     *
     * @param  array<string, mixed>  $data
     */
    private function coinsToReverse(int $credited, array $data, string $transactionTotal): int
    {
        $items = $data['items'] ?? [];

        $isFull = $items !== [] && collect($items)->every(fn (array $item): bool => ($item['type'] ?? null) === 'full');

        if ($isFull) {
            return $credited;
        }

        $refunded = (int) ($data['totals']['total'] ?? 0);
        $total = (int) $transactionTotal;

        if ($refunded < 1 || $total < 1) {
            return $credited;
        }

        return min($credited, (int) round($credited * $refunded / $total));
    }
}
