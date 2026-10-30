<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A heading over some of a dish's add-ons ("Sauces"), with the fewest and
 * most a guest picks from it: "pick up to 2", "pick at least 1". Its
 * add-ons go with it. For now only a POS connection makes groups.
 */
class DishAddonGroup extends Model
{
    /** @use HasFactory<\Database\Factories\DishAddonGroupFactory> */
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = [
        'dish_id',
        'name',
        'min_select',
        'max_select',
        'display_order',
    ];

    protected $attributes = [
        'pos_id' => null,
        'max_select' => null,
    ];

    protected function casts(): array
    {
        return [
            'dish_id' => 'integer',
            'min_select' => 'integer',
            'max_select' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /** @return HasMany<DishAddon, $this> */
    public function addons(): HasMany
    {
        return $this->hasMany(DishAddon::class)->orderBy('display_order')->orderBy('id');
    }
}
