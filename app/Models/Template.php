<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * A menu layout. Pure design plus a price in coins — it grants no features.
 * Everything variable about a template lives in two columns: `price` (0 = free)
 * and `settings_schema`, the list of knobs the owner may turn. Adding a
 * template is a row plus a Blade view of the same slug.
 */
class Template extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'price',
        'settings_schema',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'settings_schema' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(TemplatePurchase::class);
    }

    /**
     * @param  Builder<Template>  $query
     * @return Builder<Template>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    public function isFree(): bool
    {
        return $this->price === 0;
    }

    /**
     * The settings this template exposes, as declared rows.
     *
     * @return array<int, array{key: string, type: string, default: mixed}>
     */
    public function settingsSchema(): array
    {
        return $this->settings_schema ?? [];
    }

    /**
     * The schema collapsed to key => default, which is what a restaurant's
     * `template_settings` is seeded with when the template is selected.
     *
     * @return array<string, mixed>
     */
    public function defaultSettings(): array
    {
        return collect($this->settingsSchema())
            ->filter(fn (array $field): bool => isset($field['key']))
            ->mapWithKeys(fn (array $field): array => [$field['key'] => $field['default'] ?? null])
            ->all();
    }

    /**
     * Merge an owner's stored settings over the template defaults, ignoring
     * nulls and any key the schema doesn't declare.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function resolveSettings(array $stored = []): array
    {
        $defaults = $this->defaultSettings();

        return array_merge(
            $defaults,
            array_filter(
                array_intersect_key($stored, $defaults),
                fn ($value): bool => $value !== null
            )
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
