<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * A bundle of coins sold through Paddle — the only thing real money buys.
 * Everything priced in-app is then an integer of coins, so adding a purchasable
 * product never touches the billing pipeline again.
 */
class CoinPack extends Model
{
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = [
        'slug',
        'name',
        'coins',
        'paddle_price_id_sandbox',
        'paddle_price_id_production',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'coins' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @param  Builder<CoinPack>  $query
     * @return Builder<CoinPack>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The Paddle price id for the environment we're running against. A pack
     * without one isn't sellable here yet.
     */
    public function paddlePriceId(): ?string
    {
        $id = config('cashier.sandbox')
            ? $this->paddle_price_id_sandbox
            : $this->paddle_price_id_production;

        return filled($id) ? $id : null;
    }

    public function isSellable(): bool
    {
        return $this->is_active && $this->paddlePriceId() !== null;
    }

    /**
     * @param  Builder<CoinPack>  $query
     * @return Builder<CoinPack>
     */
    public function scopeSellable(Builder $query): Builder
    {
        $column = config('cashier.sandbox')
            ? 'paddle_price_id_sandbox'
            : 'paddle_price_id_production';

        return $query->active()->whereNotNull($column)->where($column, '!=', '');
    }

    /** Resolve a pack by the Paddle price that was actually paid. */
    public static function findByPaddlePriceId(string $priceId): ?self
    {
        $column = config('cashier.sandbox')
            ? 'paddle_price_id_sandbox'
            : 'paddle_price_id_production';

        return static::query()->where($column, $priceId)->first();
    }
}
