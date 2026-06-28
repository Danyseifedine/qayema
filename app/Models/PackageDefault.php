<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * The base/floor limit applied when a restaurant has no granting package. One
 * row per limit slug (e.g. dish_limit, category_limit). The resolved map is
 * cached and read by App\Services\Global\Package as the fallback; per-package
 * values and purchased slots overlay on top of these.
 */
class PackageDefault extends Model
{
    private const CACHE_KEY = 'package_defaults';

    protected $fillable = [
        'slug',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'integer',
        ];
    }

    /**
     * The cached slug => value map of every default limit.
     *
     * @return array<string, int>
     */
    public static function map(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            (int) config('package.cache_ttl', 300),
            fn (): array => self::query()->pluck('value', 'slug')->map(fn ($value): int => (int) $value)->all(),
        );
    }

    /**
     * The default limit for a slug, or $fallback when it isn't configured
     * (matching the previous config(..., 0) behaviour).
     */
    public static function limit(string $slug, int $fallback = 0): int
    {
        return self::map()[$slug] ?? $fallback;
    }

    /** Set (or create) a default limit and refresh the cache. */
    public static function set(string $slug, int $value): void
    {
        self::query()->updateOrCreate(['slug' => $slug], ['value' => $value]);
        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    protected static function booted(): void
    {
        $flush = fn (): bool => Cache::forget(self::CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }
}
