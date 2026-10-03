<?php

namespace App\Models;

use App\Enums\Feature;
use App\Services\Packages\Entitlements;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Once;
use Spatie\Translatable\HasTranslations;

/**
 * A plan a restaurant can be on. Everything variable lives in `features`, a map
 * of App\Enums\Feature slug => value: an integer allowance, null for unlimited,
 * or 0/1 for a flag. A key the map doesn't carry falls back to that feature's
 * own default, so adding an enum case never breaks an existing package.
 *
 * The rows are fixed (four, seeded by the migration); an admin edits what they
 * contain at /admin → Packages.
 */
class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory, HasTranslations;

    private const DEFAULT_CACHE_KEY = 'package:default';

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price_cents',
        'currency',
        'is_contact_only',
        'is_default',
        'sort_order',
        'is_featured',
        'features',
        'fair_use',
    ];

    protected function casts(): array
    {
        return [
            'price_cents' => 'integer',
            'is_contact_only' => 'boolean',
            'is_default' => 'boolean',
            'sort_order' => 'integer',
            'is_featured' => 'boolean',
            'features' => 'array',
            'fair_use' => 'array',
        ];
    }

    protected static function booted(): void
    {
        // A package's numbers sit underneath every restaurant on it, and the
        // default sits underneath every restaurant without one, so any write
        // here has to invalidate all of them rather than guess which.
        $flush = function (): void {
            Cache::forget(self::DEFAULT_CACHE_KEY);
            // default() keeps its answer for the request (once()).
            Once::flush();
            Entitlements::flushAll();
        };

        static::saved($flush);
        static::deleted($flush);

        // Every restaurant falls back on the default package, so it is never
        // deleted. Restaurants on any other one move to the default first,
        // through the assigner, so their history says why.
        static::deleting(function (self $package): bool {
            if ($package->is_default) {
                return false;
            }

            $default = self::default();
            $assigner = app(\App\Services\Packages\PackageAssigner::class);

            foreach ($default === null ? [] : $package->restaurants()->get() as $restaurant) {
                $assigner->assign($restaurant, $default, note: "The {$package->name} package was deleted.");
            }

            return true;
        });

        // "Most popular" is one package: marking one takes it off the others.
        static::saved(function (self $package): void {
            if ($package->is_featured && ($package->wasRecentlyCreated || $package->wasChanged('is_featured'))) {
                self::query()->whereKeyNot($package->getKey())->where('is_featured', true)->update(['is_featured' => false]);
            }
        });
    }

    /**
     * What a new restaurant starts on, and where an expired package lands.
     * Read once per request: the admin's restaurant list asks for it on
     * every row, and the home page on every visit.
     */
    public static function default(): ?self
    {
        return once(function (): ?self {
            $id = Cache::remember(
                self::DEFAULT_CACHE_KEY,
                (int) config('package.cache_ttl', 300),
                fn (): ?int => self::query()->where('is_default', true)->value('id'),
            );

            return $id === null ? null : self::query()->find($id);
        });
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::query()->firstWhere('slug', $slug);
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    /** This package's value for a feature, or null when it is unlimited. */
    /**
     * A limit shown to owners as unlimited while a fair-use number holds in
     * practice (Premium's dishes and categories): the number in `features`
     * is enforced, "Unlimited" is what the dashboard and pricing say, and the
     * pricing page and Terms state the fair-use number.
     */
    public function isFairUse(Feature $feature): bool
    {
        return $feature->isLimit()
            && in_array($feature->value, (array) $this->fair_use, true)
            && $this->featureValue($feature) !== null;
    }

    /** What owners are shown: null (unlimited) for a fair-use limit, else the value. */
    public function shownValue(Feature $feature): ?int
    {
        return $this->isFairUse($feature) ? null : $this->featureValue($feature);
    }

    public function featureValue(Feature $feature): ?int
    {
        $features = $this->features ?? [];

        if (! array_key_exists($feature->value, $features)) {
            return $feature->defaultValue();
        }

        $value = $features[$feature->value];

        return $value === null ? null : (int) $value;
    }
}
