<?php

namespace App\Models;

use App\Enums\Feature;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * The floor limit (or flag) every restaurant starts from, editable in the admin
 * panel. Grants in `restaurant_features` stack on top; nothing else overrides
 * these, so raising a default raises it for everyone at once.
 */
class FeatureDefault extends Model
{
    private const CACHE_KEY = 'feature_defaults';

    protected $fillable = [
        'feature',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'feature' => Feature::class,
            'value' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn (): bool => Cache::forget(self::CACHE_KEY);

        static::saved($flush);
        static::deleted($flush);
    }

    /**
     * The cached slug => value map of every default.
     *
     * @return array<string, int>
     */
    public static function map(): array
    {
        return Cache::remember(
            self::CACHE_KEY,
            (int) config('package.cache_ttl', 300),
            fn (): array => self::query()->pluck('value', 'feature')
                ->map(fn ($value): int => (int) $value)
                ->all(),
        );
    }

    /**
     * The default for a feature, falling back to the enum's own starting value
     * when the row is missing (a half-seeded test database, say).
     */
    public static function for(Feature $feature): int
    {
        return self::map()[$feature->value] ?? $feature->defaultValue();
    }

    public static function set(Feature $feature, int $value): void
    {
        self::query()->updateOrCreate(['feature' => $feature->value], ['value' => $value]);
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
