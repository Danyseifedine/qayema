<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

/**
 * A menu section: a name, an optional line describing it, and a position.
 * No image.
 */
class Category extends Model
{
    use HasFactory, HasTranslations;

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    protected $fillable = [
        'restaurant_id',
        'name',
        'description',
        'display_order',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'display_order' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class)->orderBy('display_order');
    }
}
