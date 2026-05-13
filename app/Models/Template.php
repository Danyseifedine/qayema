<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Template extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'fields',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
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

    /**
     * Build a key → default map from this template's fields schema.
     */
    public function defaultSettings(): array
    {
        return collect($this->fields ?? [])
            ->mapWithKeys(fn ($field) => [$field['key'] => $field['default'] ?? null])
            ->all();
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
