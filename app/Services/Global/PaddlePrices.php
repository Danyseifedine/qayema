<?php

namespace App\Services\Global;

use App\Models\CoinPack;
use Illuminate\Support\Facades\Cache;
use InvalidArgumentException;
use Laravel\Paddle\Cashier;

/**
 * Live Paddle amounts for the coin packs. Paddle stays the billing source of
 * truth — we read what it will actually charge rather than storing a second
 * copy of the price that could drift.
 */
class PaddlePrices
{
    private const CACHE_KEY = 'paddle:coin-pack-prices';

    /**
     * Amounts keyed by pack slug: ['starter' => ['amount' => 500, 'currency' => 'USD']].
     * Amounts are in the currency's smallest unit (cents). Returns [] when
     * Paddle is unreachable so callers degrade to "price unavailable".
     *
     * @return array<string, array{amount: int, currency: string}>
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
     * Push a new price to Paddle for a pack, then flush so every surface shows
     * the new amount immediately.
     */
    public function update(CoinPack $pack, int $amountCents, string $currency = 'USD'): void
    {
        $priceId = $pack->paddlePriceId();

        if ($priceId === null) {
            throw new InvalidArgumentException("Coin pack [{$pack->slug}] has no Paddle price for this environment.");
        }

        Cashier::api('PATCH', 'prices/'.$priceId, [
            'unit_price' => [
                'amount' => (string) $amountCents,
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
     * @return array<string, array{amount: int, currency: string}>
     */
    private function fetch(): array
    {
        $packs = CoinPack::query()->sellable()->get();

        if ($packs->isEmpty()) {
            return [];
        }

        $ids = $packs->map(fn (CoinPack $pack): ?string => $pack->paddlePriceId())
            ->filter()
            ->unique()
            ->values();

        $response = Cashier::api('GET', 'prices', ['id' => $ids->implode(',')]);

        $prices = collect($response['data'] ?? [])->keyBy('id');

        $amounts = [];

        foreach ($packs as $pack) {
            $price = $prices->get($pack->paddlePriceId());

            if ($price !== null && isset($price['unit_price']['amount'])) {
                $amounts[$pack->slug] = [
                    'amount' => (int) $price['unit_price']['amount'],
                    'currency' => $price['unit_price']['currency_code'] ?? 'USD',
                ];
            }
        }

        return $amounts;
    }
}
