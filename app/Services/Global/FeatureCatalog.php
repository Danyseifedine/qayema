<?php

namespace App\Services\Global;

class FeatureCatalog
{
    /**
     * The purchasable feature catalog, keyed by cart id, with each entry's
     * price_id resolved for the active Paddle environment (sandbox vs live).
     * Only entries with a configured price are sellable, so an id not yet
     * created for this environment silently drops that entry.
     *
     * @return array<string, array{price_id: string, slug: string, kind: string, step: int}>
     */
    public function all(): array
    {
        $environment = config('cashier.sandbox') ? 'sandbox' : 'production';

        $catalog = [];

        foreach (config('paddle.catalog', []) as $id => $entry) {
            $priceId = is_array($entry['price_id'] ?? null)
                ? ($entry['price_id'][$environment] ?? null)
                : ($entry['price_id'] ?? null);

            if (empty($priceId)) {
                continue;
            }

            $entry['price_id'] = $priceId;
            $catalog[$id] = $entry;
        }

        return $catalog;
    }

    /**
     * Resolve a cart id (e.g. "dish") to its catalog entry.
     *
     * @return array{price_id: string, slug: string, kind: string, step: int}|null
     */
    public function find(string $id): ?array
    {
        return $this->all()[$id] ?? null;
    }

    /**
     * Resolve a paid Paddle price id back to its catalog entry.
     *
     * @return array{price_id: string, slug: string, kind: string, step: int}|null
     */
    public function findByPriceId(string $priceId): ?array
    {
        foreach ($this->all() as $entry) {
            if ($entry['price_id'] === $priceId) {
                return $entry;
            }
        }

        return null;
    }
}
