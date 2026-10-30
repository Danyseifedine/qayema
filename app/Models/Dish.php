<?php

namespace App\Models;

use App\Models\Concerns\ClearsMenuCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

/**
 * A menu item: name, price, ingredients and one image. No description. It
 * may carry variants (pick one option of each) and add-ons (pick any), each
 * adding to the price. An offer may make one of its own (its deal dish,
 * `offer_id`, Offer::deal()), which guests meet only while that offer is
 * live; `offer_id` is set by the offer API alone, never by a dish save.
 */
class Dish extends Model implements HasMedia
{
    use ClearsMenuCache, HasFactory, HasTranslations, InteractsWithMedia;

    /** @var string[] */
    public array $translatable = ['name', 'ingredients'];

    protected $fillable = [
        'restaurant_id',
        'category_id',
        'icon',
        'name',
        'ingredients',
        'price',
        'currency',
        'is_available',
        'display_order',
    ];

    /**
     * Priced in the restaurant's main currency until a second one is picked,
     * and no offer's deal until one makes it (strict models).
     */
    protected $attributes = [
        'currency' => null,
        'offer_id' => null,
        'icon' => null,
        'pos_id' => null,
        'pos_removed_at' => null,
        'pos_image_version' => null,
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'category_id' => 'integer',
            'offer_id' => 'integer',
            'price' => 'decimal:2',
            'is_available' => 'boolean',
            'display_order' => 'integer',
            'pos_removed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * The offer that made this dish (its deal), when one did: the dish is
     * on the menu only while that offer is live.
     *
     * @return BelongsTo<Offer, $this>
     */
    public function offer(): BelongsTo
    {
        return $this->belongsTo(Offer::class);
    }

    /** An offer's deal dish, guests meet it only while that offer is live. */
    public function isDeal(): bool
    {
        return $this->offer_id !== null;
    }

    /** @return HasMany<DishVariant, $this> */
    public function variants(): HasMany
    {
        return $this->hasMany(DishVariant::class)->orderBy('display_order')->orderBy('id');
    }

    /** @return HasMany<DishAddon, $this> */
    public function addons(): HasMany
    {
        return $this->hasMany(DishAddon::class)->orderBy('display_order')->orderBy('id');
    }

    /** @return HasMany<DishAddonGroup, $this> */
    public function addonGroups(): HasMany
    {
        return $this->hasMany(DishAddonGroup::class)->orderBy('display_order')->orderBy('id');
    }

    /** @return HasMany<DishBuild, $this> */
    public function builds(): HasMany
    {
        return $this->hasMany(DishBuild::class)->orderBy('display_order')->orderBy('id');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * The photo as the menu card draws it (72px, so 240px stays sharp on a
     * 3x phone screen): a few KB instead of the full photo, which only the
     * dish's sheet loads. Made with the upload, not on a queue that may not
     * run. A photo stored before this has none; the menu falls back to the
     * full one until `media-library:regenerate` makes it.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        // The card's picture: the whole photo in its own shape, never
        // cropped or enlarged; the card fits it in its square.
        $this->addMediaConversion('thumb')
            ->performOnCollections('image')
            ->fit(Fit::Max, 240, 240)
            ->format('webp')
            ->quality(76)
            ->nonQueued();
    }
}
