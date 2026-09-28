<?php

namespace App\Models;

use App\Services\Security\AbuseGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlockedIp extends Model
{
    use HasFactory;

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
        $forget = fn (self $block) => app(AbuseGuard::class)->forgetBlockCache($block->ip);

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
}
