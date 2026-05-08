<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantStatistic extends Model
{
    protected $fillable = [
        'restaurant_id',
        'session_id',
        'device_type',
        'browser',
        'os',
        'viewed_at',
        'time_spent',
        'page_views',
        'via_qr',
        'whatsapp_orders',
    ];

    protected function casts(): array
    {
        return [
            'viewed_at' => 'datetime',
            'time_spent' => 'integer',
            'page_views' => 'integer',
            'via_qr' => 'boolean',
            'whatsapp_orders' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
