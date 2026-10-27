<?php

namespace App\Models;

use App\Enums\Feature;
use App\Enums\GrantMode;
use App\Models\Concerns\ClearsMenuCache;
use App\Observers\FeatureGrantObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One grant an admin gave a restaurant. Added (`GrantMode::Add`), limits add
 * their `value` to the allowance and flags switch theirs on. Set
 * (`GrantMode::Set`), the feature is `value` for this restaurant whatever
 * the package says: a limit (null for unlimited) or a flag (0 off, 1 on).
 */
#[ObservedBy(FeatureGrantObserver::class)]
class FeatureGrant extends Model
{
    use ClearsMenuCache, HasFactory;

    /**
     * What a new row holds before it is read back: the mode the database
     * fills itself (strict models).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'mode' => 'add',
    ];

    protected $fillable = [
        'restaurant_id',
        'feature',
        'mode',
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
            'mode' => GrantMode::class,
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
