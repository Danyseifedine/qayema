<?php

namespace App\Models;

use App\Enums\Feature;
use App\Observers\PackageObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
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
#[ObservedBy(PackageObserver::class)]
class Package extends Model
{
    /** @use HasFactory<\Database\Factories\PackageFactory> */
    use HasFactory, HasTranslations;

    public const DEFAULT_CACHE_KEY = 'package:default';

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
        'is_active',
        'highlights',
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
            'is_active' => 'boolean',
            'highlights' => 'array',
            'features' => 'array',
            'fair_use' => 'array',
        ];
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

    /**
     * Packages on offer, in their order: what the landing page, the
     * dashboard's Package page and package requests know about.
     *
     * @param  Builder<self>  $query
     */
    public function scopeOffered(Builder $query): void
    {
        $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The card's own lines in a language (English when that one has none),
     * or empty: the card then lists what the package adds, from its features.
     *
     * @return list<string>
     */
    public function highlightsIn(string $locale): array
    {
        return $this->writtenHighlights($locale) ?: $this->writtenHighlights('en');
    }

    /**
     * The lines the admin wrote in exactly this language.
     *
     * @return list<string>
     */
    public function writtenHighlights(string $language): array
    {
        return array_values(array_filter(
            array_map('trim', (array) ($this->highlights[$language] ?? [])),
            fn (string $line): bool => $line !== '',
        ));
    }

    /**
     * The packages on offer, read once per request: the landing page's
     * cards and its search data both list them. A package save flushes it
     * (once()).
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, self>
     */
    public static function onOffer(): \Illuminate\Database\Eloquent\Collection
    {
        return once(fn () => self::query()->offered()->get());
    }

    public static function findBySlug(string $slug): ?self
    {
        return self::query()->firstWhere('slug', $slug);
    }

    /** @return HasMany<Restaurant, $this> */
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
