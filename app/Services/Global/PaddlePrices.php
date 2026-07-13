<?php

namespace App\Services\Global;

use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Laravel\Paddle\Cashier;

class PaddlePrices
{
    private const CACHE_KEY = 'paddle:prices';

    public function __construct(private readonly FeatureCatalog $catalog) {}

    /**
     * Live Paddle amounts for every sellable catalog entry, keyed by cart id:
     * ['dish' => ['unit_amount' => 10, 'currency' => 'USD']]. Amounts are in the
     * currency's smallest unit (cents). Cached briefly so the dashboard and SPA
     * don't hammer Paddle; returns [] when Paddle is unreachable so callers
     * degrade to "price unavailable" instead of erroring.
     *
     * @return array<string, array{unit_amount: int, currency: string}>
     */
    public function amounts(): array
    {
        try {
            return Cache::remember(
                self::CACHE_KEY,
                (int) config('package.cache_ttl', 300),
                fn (): array => $this->fetch(),
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Push a new unit price to Paddle for a catalog entry, then flush the cache
     * so every surface shows the new amount immediately.
     */
    public function update(string $cartId, int $unitAmountCents, string $currency = 'USD'): void
    {
        $entry = $this->catalog->find($cartId);

        if ($entry === null) {
            throw new InvalidArgumentException("Unknown or unsellable catalog entry [{$cartId}].");
        }

        Cashier::api('PATCH', 'prices/'.$entry['price_id'], [
            'unit_price' => [
                'amount' => (string) $unitAmountCents,
                'currency_code' => $currency,
            ],
        ]);

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, array{unit_amount: int, currency: string}>
     */
    private function fetch(): array
    {
        $entries = $this->catalog->all();

        if ($entries === []) {
            return [];
        }

        $ids = array_values(array_unique(array_column($entries, 'price_id')));

        $response = Cashier::api('GET', 'prices', ['id' => implode(',', $ids)]);

        $prices = collect($response['data'] ?? [])->keyBy('id');

        $amounts = [];

        foreach ($entries as $id => $entry) {
            $price = $prices->get($entry['price_id']);

            if ($price !== null && isset($price['unit_price']['amount'])) {
                $amounts[$id] = [
                    'unit_amount' => (int) $price['unit_price']['amount'],
                    'currency' => $price['unit_price']['currency_code'] ?? 'USD',
                ];
            }
        }

        return $amounts;
    }
}
