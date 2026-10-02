<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

/**
 * An extra a guest may add to a dish (Extra cheese), any number of them,
 * each adding its price.
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

    protected function casts(): array
    {
        return [
            'dish_id' => 'integer',
            'price' => 'decimal:2',
            'display_order' => 'integer',
        ];
    }
}
