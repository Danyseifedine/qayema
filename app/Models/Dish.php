<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Dish extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'menu_id',
        'category_id',
        'name',
        'description',
        'price',
        'ingredients',
        'is_available',
        'display_order',
    ];

    protected $casts = [
        'menu_id' => 'integer',
        'category_id' => 'integer',
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get the menu that owns the dish.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Get the category that owns the dish.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Register media collections.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
