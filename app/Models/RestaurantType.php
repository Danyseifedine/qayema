<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class RestaurantType extends Model
{
    use HasTranslations;

    /** @var string[] */
    public array $translatable = ['name'];

    protected $fillable = ['name', 'slug', 'icon'];

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }
}
