<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One guest's visit to a menu: what the Analytics page and the admin panel
 * count. `stats:rollup` adds each finished day up into `menu_days`, then
 * deletes the rows older than 6 months.
 */
class MenuSession extends Model
{
    /** @use HasFactory<\Database\Factories\MenuSessionFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'session_id',
        'device_type',
        'browser',
        'os',
        'locale',
        'viewed_at',
        'via_qr',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'viewed_at' => 'datetime',
            'via_qr' => 'boolean',
        ];
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
