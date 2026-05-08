<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RestaurantType extends Model
{
    protected $fillable = ['name', 'slug', 'icon'];

    public function restaurants(): HasMany
    {
        return $this->hasMany(Restaurant::class);
    }
}
