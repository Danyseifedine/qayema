<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

/**
 * A menu item: name, price, ingredients and one image. No description. It
 * may carry variants (pick one option of each) and add-ons (pick any), each
 * adding to the price.
 */
class Dish extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    /** @var string[] */
    public array $translatable = ['name', 'ingredients'];

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'name',
        'ingredients',
        'price',
        'is_available',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'category_id' => 'integer',
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'display_order' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(DishVariant::class)->orderBy('display_order')->orderBy('id');
    }

    public function addons(): HasMany
    {
        return $this->hasMany(DishAddon::class)->orderBy('display_order')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }
}
