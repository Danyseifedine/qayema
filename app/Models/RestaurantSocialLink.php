<?php

namespace App\Models;

use App\Models\Concerns\ClearsMenuCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantSocialLink extends Model
{
    use ClearsMenuCache, HasFactory;

    /** Platforms whose icon the menu can render. */
    public const PLATFORMS = ['instagram', 'x', 'facebook', 'tiktok'];

    protected $fillable = [
        'restaurant_id',
        'platform',
        'url',
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
