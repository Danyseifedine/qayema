<?php

namespace App\Models;

use App\Services\MenuOwner\Entitlements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Subscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'subscribable_type',
        'subscribable_id',
        'period',
        'price',
        'currency',
        'status',
        'trial_ends_at',
        'starts_at',
        'current_period_end',
        'canceled_at',
        'provider',
        'provider_ref',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'trial_ends_at' => 'datetime',
            'starts_at' => 'datetime',
            'current_period_end' => 'datetime',
            'canceled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn (self $subscription) => Entitlements::flush($subscription->restaurant_id);

        static::saved($flush);
        static::deleted($flush);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function subscribable(): MorphTo
    {
        return $this->morphTo();
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    /**
     * Entitling subscriptions: trialing/active within the period, or past_due
     * within the configured grace window.
     */
    public function scopeActive(Builder $query): Builder
    {
        $grace = (int) config('entitlements.grace_days', 7);

        return $query->where(function (Builder $query) use ($grace) {
            $query->where(function (Builder $query) {
                $query->whereIn('status', ['trialing', 'active'])
                    ->where('current_period_end', '>', now());
            })->orWhere(function (Builder $query) use ($grace) {
                $query->where('status', 'past_due')
                    ->where('current_period_end', '>', now()->subDays($grace));
            });
        });
    }

    public function isEntitling(): bool
    {
        $grace = (int) config('entitlements.grace_days', 7);

        return match ($this->status) {
            'trialing', 'active' => $this->current_period_end->isFuture(),
            'past_due' => $this->current_period_end->gt(now()->subDays($grace)),
            default => false,
        };
    }
}
