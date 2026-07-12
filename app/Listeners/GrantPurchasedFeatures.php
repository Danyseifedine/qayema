<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\Global\FeatureFulfillment;
use Laravel\Paddle\Events\TransactionCompleted;

class GrantPurchasedFeatures
{
    public function __construct(private readonly FeatureFulfillment $fulfillment) {}

    /**
     * Turn a completed Paddle transaction into package feature grants. Cashier
     * only fires this once per transaction (it guards on the transaction already
     * existing), so this runs at-most-once per payment.
     */
    public function handle(TransactionCompleted $event): void
    {
        if (! $event->billable instanceof User) {
            return;
        }

        $items = $event->payload['data']['items'] ?? [];

        $this->fulfillment->fulfill($event->billable, $event->transaction->paddle_id, $items);
    }
}
