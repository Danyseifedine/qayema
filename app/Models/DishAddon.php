<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

/**
 * An extra a guest may add to a dish (Extra cheese), each adding its price:
 * any number of them, or within its group's limits when it has a group
 * (DishAddonGroup).
 */
class DishAddon extends Model
{
    /** @use HasFactory<\Database\Factories\DishAddonFactory> */
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = [
        'dish_id',
        'name',
        'price',
        'display_order',
    ];

    protected $attributes = [
        'dish_addon_group_id' => null,
        'pos_id' => null,
    ];

    protected function casts(): array
    {
        return [
            'dish_id' => 'integer',
            'dish_addon_group_id' => 'integer',
            'price' => 'decimal:2',
            'display_order' => 'integer',
        ];
    }

    /** @return BelongsTo<DishAddonGroup, $this> */
    public function group(): BelongsTo
    {
        return $this->belongsTo(DishAddonGroup::class, 'dish_addon_group_id');
    }
}
