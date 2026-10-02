<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A choice on a dish that the owner names (Size, Spice level, Quantity). The
 * guest picks exactly one of its options.
 */
class DishVariant extends Model
{
    /** @use HasFactory<\Database\Factories\DishVariantFactory> */
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = [
        'dish_id',
        'name',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'dish_id' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function options(): HasMany
    {
        return $this->hasMany(DishVariantOption::class)->orderBy('display_order')->orderBy('id');
    }
}
