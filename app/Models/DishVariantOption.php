<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * One option of a variant (Large, Hot). Its price is added to the dish's,
 * and is 0 when the choice costs nothing more.
 */
class DishVariantOption extends Model
{
    /** @use HasFactory<\Database\Factories\DishVariantOptionFactory> */
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = [
        'dish_variant_id',
        'name',
        'price',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'dish_variant_id' => 'integer',
            'price' => 'decimal:2',
            'display_order' => 'integer',
        ];
    }
}
