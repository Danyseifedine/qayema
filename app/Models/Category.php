<?php

namespace App\Models;

use App\Models\Concerns\ClearsMenuCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A menu section: a name, an optional line describing it, an optional
 * icon (App\Support\CategoryIcons) and a position. No image.
 */
class Category extends Model
{
    use ClearsMenuCache, HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'icon',
        'display_order',
    ];

    protected $attributes = [
        'pos_id' => null,
        'pos_removed_at' => null,
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'display_order' => 'integer',
            'pos_removed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** @return HasMany<Dish, $this> */
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class)->orderBy('display_order');
    }
}
