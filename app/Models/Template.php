<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Template extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'tier',
        'capabilities',
        'default_settings',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'capabilities' => 'array',
            'default_settings' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'template_tag');
    }

    public function features(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'template_feature')->withPivot('value');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(TemplatePrice::class);
    }

    public function subscriptions(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Subscription::class, 'subscribable');
    }

    public function isFree(): bool
    {
        return ($this->tier ?? 'free') !== 'paid';
    }

    public function isPaid(): bool
    {
        return ! $this->isFree();
    }

    public function capability(string $key, bool $default = true): bool
    {
        return (bool) (($this->capabilities ?? [])[$key] ?? $default);
    }

    public function defaultSettings(): array
    {
        return $this->default_settings ?? [];
    }

    /**
     * Merge stored settings over the template defaults, ignoring null stored values.
     */
    public function resolveSettings(array $stored = []): array
    {
        return array_merge(
            $this->defaultSettings(),
            array_filter($stored, fn ($v) => $v !== null)
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('thumbnail')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
