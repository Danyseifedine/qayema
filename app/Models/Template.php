<?php

namespace App\Models;

use App\Support\Color;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * A menu layout. Pure design — it grants no features and costs nothing: every
 * active template is open to every restaurant. The only variable part is
 * `settings_schema`, the list of knobs the owner may turn. Adding a template
 * is a row plus a Blade view of the same slug.
 */
class Template extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Qayema's own gold: the accent a menu has until its owner picks one.
     */
    public const DEFAULT_PRIMARY_COLOR = '#F8D38D';

    /**
     * The knobs the classic design exposes, and what a scaffolded template
     * starts from (TemplateSeeder, make:menu-template). `label` is what the
     * owner reads on Colors & fonts; `contrast_with` names the colour this
     * one is read against, so the dashboard can warn when they clash.
     *
     * @var array<int, array{key: string, type: string, default: string, label: array{en: string, ar: string}, contrast_with?: string}>
     */
    public const CLASSIC_SCHEMA = [
        ['key' => 'primary_color', 'type' => 'color', 'default' => self::DEFAULT_PRIMARY_COLOR,
            'label' => ['en' => 'Main colour', 'ar' => 'اللون الرئيسي']],
        ['key' => 'background_color', 'type' => 'color', 'default' => '#FFFFFF',
            'label' => ['en' => 'Background', 'ar' => 'الخلفية']],
        ['key' => 'text_color', 'type' => 'color', 'default' => '#111418',
            'label' => ['en' => 'Text', 'ar' => 'النص'], 'contrast_with' => 'background_color'],
    ];

    /**
     * A setting's key: it becomes a CSS variable, a form path and a validation
     * rule, so it is kept to lowercase words joined by underscores.
     */
    public const SETTING_KEY_PATTERN = '/^[a-z][a-z0-9_]*$/';

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'slug',
        'name',
        'description',
        'settings_schema',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'settings_schema' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    /**
     * @param  Builder<Template>  $query
     * @return Builder<Template>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * The settings this template exposes, as declared rows.
     *
     * @return array<int, array{key: string, type: string, default: mixed, label?: array<string, string|null>, contrast_with?: string|null, options?: array<int, string>}>
     */
    public function settingsSchema(): array
    {
        return $this->settings_schema ?? [];
    }

    /**
     * The colour rows alone, with a usable key: what the owner edits on the
     * dashboard's Colors & fonts page and what the menu gets as CSS variables.
     *
     * @return array<int, array{key: string, type: string, default: mixed, label?: array<string, string|null>, contrast_with?: string|null}>
     */
    public function colorSettings(): array
    {
        return array_values(array_filter(
            $this->settingsSchema(),
            fn (array $row): bool => ($row['type'] ?? null) === 'color'
                && is_string($row['key'] ?? null)
                && preg_match(self::SETTING_KEY_PATTERN, $row['key']) === 1,
        ));
    }

    /**
     * The schema collapsed to key => default: what a design looks like
     * before its owner changes anything.
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
     * nulls, any key the schema doesn't declare, and a colour that isn't a
     * hex — views print these straight into CSS, where a stray `;}` would
     * break out of the rule.
     *
     * @param  array<string, mixed>  $stored
     * @return array<string, mixed>
     */
    public function resolveSettings(array $stored = []): array
    {
        $defaults = $this->defaultSettings();
        $colors = array_column($this->colorSettings(), 'key');

        return array_merge(
            $defaults,
            array_filter(
                array_intersect_key($stored, $defaults),
                fn ($value, string $key): bool => $value !== null
                    && (! in_array($key, $colors, true) || Color::isHex($value)),
                ARRAY_FILTER_USE_BOTH,
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
