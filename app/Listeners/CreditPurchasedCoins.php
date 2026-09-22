<?php

namespace App\Listeners;

use App\Enums\CoinTransactionType;
use App\Models\CoinPack;
use App\Models\User;
use Laravel\Paddle\Events\TransactionCompleted;

/**
 * Turn a completed Paddle transaction into coins. Every line item is resolved
 * back to a coin pack by the price that was paid, so the amount credited comes
 * from our own row rather than anything the browser sent.
 */
class CreditPurchasedCoins
{
    public function handle(TransactionCompleted $event): void
    {
        if (! $event->billable instanceof User) {
            return;
        }

        $items = $event->payload['data']['items'] ?? [];
        $transactionId = $event->transaction->paddle_id;

        $coins = 0;
        $packs = [];

        foreach ($items as $item) {
            $priceId = $item['price']['id'] ?? $item['price_id'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 1);

            if ($priceId === null || $quantity < 1) {
                continue;
            }

            $pack = CoinPack::findByPaddlePriceId($priceId);

            if ($pack === null) {
                continue;
            }

            $coins += $pack->coins * $quantity;
            $packs[] = ['pack' => $pack->slug, 'quantity' => $quantity, 'coins' => $pack->coins * $quantity];
        }

        if ($coins < 1) {
            return;
        }

        // Keyed on the Paddle transaction id: a re-delivered webhook credits
        // nothing the second time.
        $event->billable->wallet()->credit(
            $coins,
            CoinTransactionType::Purchase,
            $transactionId,
            ['packs' => $packs],
        );
    }
}
