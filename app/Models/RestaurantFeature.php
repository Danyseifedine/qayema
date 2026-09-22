<?php

namespace App\Models;

use App\Enums\Feature;
use App\Services\Global\Package;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One grant stacked on top of the feature defaults. Limits add their `value` to
 * the allowance; flags switch theirs on.
 */
class RestaurantFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'feature',
        'value',
        'source',
        'reference',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'feature' => Feature::class,
            'value' => 'integer',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn (self $grant) => Package::flush($grant->restaurant_id);

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * @param  Builder<RestaurantFeature>  $query
     * @return Builder<RestaurantFeature>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $builder) => $builder->whereNull('ends_at')->orWhere('ends_at', '>', now())
        );
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
