<?php

namespace App\Models;

use App\Services\Security\AbuseGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\MassPrunable;
use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    /** @use HasFactory<\Database\Factories\BlockedIpFactory> */
    use HasFactory, MassPrunable;

    /** An ended block is kept this long for the admin to see, then pruned (model:prune). */
    public const KEPT_DAYS_AFTER_ENDING = 7;

    protected $fillable = [
        'ip',
        'reason',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $forget = fn () => app(AbuseGuard::class)->forgetBlockCache();

        static::saved($forget);
        static::deleted($forget);
    }

    /**
     * @param  Builder<BlockedIp>  $query
     * @return Builder<BlockedIp>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where(function (Builder $builder): void {
            $builder->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Blocks that ended a week ago or more; a block with no end is never pruned.
     *
     * @return Builder<BlockedIp>
     */
    public function prunable(): Builder
    {
        return static::query()->where('expires_at', '<', now()->subDays(self::KEPT_DAYS_AFTER_ENDING));
    }
}
