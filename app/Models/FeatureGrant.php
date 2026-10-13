<?php

namespace App\Models;

use App\Enums\Feature;
use App\Models\Concerns\ClearsMenuCache;
use App\Observers\FeatureGrantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One grant an admin stacked on top of the restaurant's package. Limits add their `value` to
 * the allowance; flags switch theirs on.
 */
#[ObservedBy(FeatureGrantObserver::class)]
class FeatureGrant extends Model
{
    use ClearsMenuCache, HasFactory;

    protected $fillable = [
        'restaurant_id',
        'feature',
        'value',
        'source',
        'note',
        'ends_at',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'feature' => Feature::class,
            'value' => 'integer',
            'ends_at' => 'datetime',
        ];
    }

    /**
     * What the restaurant had in reach before this grant was saved
     * (FeatureGrantObserver); never stored on the row.
     *
     * @var array<int, string>
     */
    public array $inReachBeforeSave = [];

    /**
     * @param  Builder<FeatureGrant>  $query
     * @return Builder<FeatureGrant>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(
            fn (Builder $builder) => $builder->whereNull('ends_at')->orWhere('ends_at', '>', now())
        );
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
