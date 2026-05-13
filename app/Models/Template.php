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
        // Restaurant Profile
        'has_logo',
        'has_cover_image',
        'has_description',
        'has_phone',
        'has_address',
        'has_map',
        'has_schedule',
        'has_social_links',
        // Menu Content
        'has_dish_images',
        'has_dish_ingredients',
        'has_dish_prices',
        'has_dish_tags',
        'has_category_images',
        'has_category_description',
        // UI / UX
        'has_search',
        'has_search_title',
        'has_order_page_title',
        'has_final_price_show',
        'has_share_button',
        'has_qr_code',
        // Direction
        'default_direction',
        'allows_direction_change',
    ];

    protected function casts(): array
    {
        return [
            'fields' => 'array',
            'is_active' => 'boolean',
            'has_logo' => 'boolean',
            'has_cover_image' => 'boolean',
            'has_description' => 'boolean',
            'has_phone' => 'boolean',
            'has_address' => 'boolean',
            'has_map' => 'boolean',
            'has_schedule' => 'boolean',
            'has_social_links' => 'boolean',
            'has_dish_images' => 'boolean',
            'has_dish_ingredients' => 'boolean',
            'has_dish_prices' => 'boolean',
            'has_dish_tags' => 'boolean',
            'has_category_images' => 'boolean',
            'has_category_description' => 'boolean',
            'has_search' => 'boolean',
            'has_search_title' => 'boolean',
            'has_order_page_title' => 'boolean',
            'has_final_price_show' => 'boolean',
            'has_share_button' => 'boolean',
            'has_qr_code' => 'boolean',
            'allows_direction_change' => 'boolean',
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
