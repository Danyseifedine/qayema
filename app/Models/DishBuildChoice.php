<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * One choice of a build's own list (Vanilla, $2), or a category dish's part
 * prices in a build that takes a category (`dish_id`). `part_prices` holds a
 * price for a part size ({"2": "7.00": a half at $7}); a size without one
 * pays its share of the whole.
 */
class DishBuildChoice extends Model
{
    /** @use HasFactory<\Database\Factories\DishBuildChoiceFactory> */
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = [
        'dish_build_id',
        'dish_id',
        'name',
        'price',
        'part_prices',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'dish_build_id' => 'integer',
            'dish_id' => 'integer',
            'price' => 'decimal:2',
            'part_prices' => 'array',
            'display_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Dish, $this> */
    public function dish(): BelongsTo
    {
        return $this->belongsTo(Dish::class);
    }
}
