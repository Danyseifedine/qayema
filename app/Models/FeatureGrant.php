<?php

namespace App\Models;

use App\Enums\Feature;
use App\Services\Packages\Entitlements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One grant an admin stacked on top of the restaurant's package. Limits add their `value` to
 * the allowance; flags switch theirs on.
 */
class FeatureGrant extends Model
{
    use HasFactory;

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
     * What the restaurant had in reach before this grant was saved; never
     * stored on the row.
     *
     * @var array<int, string>
     */
    private array $inReachBeforeSave = [];

    protected static function booted(): void
    {
        static::saving(function (self $grant): void {
            $grant->inReachBeforeSave = $grant->restaurant->featuresInReach();
        });

        static::saved(function (self $grant): void {
            Entitlements::flush($grant->restaurant_id);
            // A flag it grants arrives switched on, as with a new package.
            $grant->restaurant->switchOnWhatCameIntoReach($grant->inReachBeforeSave);
        });

        static::deleted(fn (self $grant) => Entitlements::flush($grant->restaurant_id));
    }

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

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
