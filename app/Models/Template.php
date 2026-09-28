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
 * A menu layout. It grants no features. Every active template is open to
 * every restaurant, except one marked `is_premium`, which needs the
 * premium_designs flag (Restaurant::menuTemplate()). The only variable part is
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
     * owner reads on the Appearance page; `contrast_with` names the colour
     * this one is read against, so the dashboard can warn when they clash.
     *
     * @var array<int, array{key: string, type: string, default: string|bool, label: array{en: string, ar: string}, contrast_with?: string}>
     */
    public const CLASSIC_SCHEMA = [
        ['key' => 'primary_color', 'type' => 'color', 'default' => self::DEFAULT_PRIMARY_COLOR,
            'label' => ['en' => 'Main colour', 'ar' => 'اللون الرئيسي']],
        ['key' => 'background_color', 'type' => 'color', 'default' => '#FFFFFF',
            'label' => ['en' => 'Background', 'ar' => 'الخلفية']],
        ['key' => 'text_color', 'type' => 'color', 'default' => '#111418',
            'label' => ['en' => 'Text', 'ar' => 'النص'], 'contrast_with' => 'background_color'],
        ['key' => 'show_name', 'type' => 'boolean', 'default' => true,
            'label' => ['en' => 'Name in the top bar', 'ar' => 'الاسم في الشريط العلوي']],
    ];

    /** The kinds of setting an owner can edit on the Appearance page. */
    public const SETTING_TYPES = ['color', 'boolean', 'select', 'text'];

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
        'is_premium',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'settings_schema' => 'array',
            'is_active' => 'boolean',
            'is_premium' => 'boolean',
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
     * The design a menu shows when the one its owner chose needs a package
     * they no longer have: the first active design every package may use.
     */
    public static function fallback(): ?self
    {
        return self::query()->active()->where('is_premium', false)->first();
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
     * Every row an owner can edit on the Appearance page: a usable key and a
     * known type.
     *
     * @return array<int, array{key: string, type: string, default: mixed, label?: array<string, string|null>, contrast_with?: string|null, options?: array<int, string>}>
     */
    public function editableSettings(): array
    {
        return array_values(array_filter(
            $this->settingsSchema(),
            fn (array $row): bool => in_array($row['type'] ?? null, self::SETTING_TYPES, true)
                && is_string($row['key'] ?? null)
                && preg_match(self::SETTING_KEY_PATTERN, $row['key']) === 1,
        ));
    }

    /**
     * The colour rows alone: what the menu gets as CSS variables.
     *
     * @return array<int, array{key: string, type: string, default: mixed, label?: array<string, string|null>, contrast_with?: string|null}>
     */
    public function colorSettings(): array
    {
        return array_values(array_filter(
            $this->editableSettings(),
            fn (array $row): bool => $row['type'] === 'color',
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
            ->mapWithKeys(fn (array $field): array => [$field['key'] => self::cast($field, $field['default'] ?? null)])
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
        $rows = collect($this->settingsSchema())->filter(fn (array $row): bool => isset($row['key']))->keyBy('key');
        $resolved = $this->defaultSettings();

        foreach (array_intersect_key($stored, $resolved) as $key => $value) {
            if (self::accepts($rows[$key], $value)) {
                $resolved[$key] = self::cast($rows[$key], $value);
            }
        }

        return $resolved;
    }

    /**
     * Whether a stored value still fits its row: the schema may have changed
     * since it was saved (a choice removed, a type changed), and anything
     * that no longer fits falls back to the default.
     *
     * @param  array<string, mixed>  $row
     */
    public static function accepts(array $row, mixed $value): bool
    {
        return match ($row['type'] ?? null) {
            'color' => Color::isHex($value),
            'boolean' => is_bool($value) || in_array($value, [0, 1, '0', '1', 'true', 'false'], true),
            'select' => in_array($value, $row['options'] ?? [], true),
            default => is_string($value) && $value !== '' && mb_strlen($value) <= 255,
        };
    }

    /**
     * An on/off value as a real boolean, whatever the admin typed as its
     * default ("false" as text would otherwise read as on).
     *
     * @param  array<string, mixed>  $row
     */
    public static function cast(array $row, mixed $value): mixed
    {
        if (($row['type'] ?? null) !== 'boolean' || $value === null) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
