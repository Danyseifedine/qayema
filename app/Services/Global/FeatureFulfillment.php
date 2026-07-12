<?php

namespace App\Services\Global;

use App\Models\Feature;
use App\Models\User;

class FeatureFulfillment
{
    public function __construct(private readonly FeatureCatalog $catalog) {}

    /**
     * Grant the features paid for by a completed Paddle transaction. Each line
     * item is mapped through the catalog to a package feature and written as a
     * `purchase` grant on the buyer's restaurant. Idempotent: a grant already
     * referencing this transaction is skipped, so a re-delivered webhook cannot
     * double-grant.
     *
     * @param  array<int, array<string, mixed>>  $items  The transaction's `data.items`.
     */
    public function fulfill(User $billable, string $transactionId, array $items): void
    {
        $restaurant = $billable->restaurant;

        if ($restaurant === null) {
            return;
        }

        foreach ($items as $item) {
            $priceId = $item['price']['id'] ?? $item['price_id'] ?? null;
            $quantity = (int) ($item['quantity'] ?? 1);

            if ($priceId === null || $quantity < 1) {
                continue;
            }

            $entry = $this->catalog->findByPriceId($priceId);

            if ($entry === null) {
                continue;
            }

            $feature = Feature::query()->where('slug', $entry['slug'])->first();

            if ($feature === null) {
                continue;
            }

            $alreadyGranted = $restaurant->featureGrants()
                ->where('feature_id', $feature->id)
                ->where('reference', $transactionId)
                ->exists();

            if ($alreadyGranted) {
                continue;
            }

            // The Paddle line quantity already carries the granted amount (dishes
            // for a limit slot, since checkout multiplied packs by the step), so
            // grant it directly; booleans just switch on.
            $value = $entry['kind'] === 'limit' ? $quantity : 1;

            $restaurant->featureGrants()->create([
                'feature_id' => $feature->id,
                'value' => (string) $value,
                'source' => 'purchase',
                'reference' => $transactionId,
                'starts_at' => now(),
            ]);
        }
    }
}
