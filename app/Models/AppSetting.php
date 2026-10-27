<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * A setting the admin changes in the panel rather than in .env, by key.
 * Every key is read from one cached map (kept until a setting is saved),
 * so reading settings costs no query.
 */
class AppSetting extends Model
{
    private const CACHE_KEY = 'app-settings';

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    /** The setting saved under $key, or $default when none is. */
    public static function read(string $key, mixed $default = null): mixed
    {
        /** @var array<string, mixed> $settings */
        $settings = Cache::memo()->rememberForever(
            self::CACHE_KEY,
            fn (): array => self::query()->pluck('value', 'key')->all(),
        );

        return $settings[$key] ?? $default;
    }

    /**
     * Saves each key => value, a null value removing its key (back to the
     * default), and lets every request read them at once.
     *
     * @param  array<string, mixed>  $values
     */
    public static function write(array $values): void
    {
        foreach ($values as $key => $value) {
            $value === null
                ? self::query()->whereKey($key)->delete()
                : self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::memo()->forget(self::CACHE_KEY);
    }
}
