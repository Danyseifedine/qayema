<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A link a restaurant's menu had before (`/{slug}`), kept so printed QR
 * codes and shared links forward to where the menu is now. Written by the
 * restaurant's own save hook whenever its slug changes.
 */
class PreviousSlug extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'slug',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
