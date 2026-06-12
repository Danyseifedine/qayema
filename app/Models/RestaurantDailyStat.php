<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantDailyStat extends Model
{
    use HasFactory;

    protected $table = 'restaurant_stats_daily';

    protected $fillable = [
        'restaurant_id',
        'date',
        'visits',
        'unique_sessions',
        'qr_visits',
        'whatsapp_orders',
        'avg_time_spent',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'visits' => 'integer',
            'unique_sessions' => 'integer',
            'qr_visits' => 'integer',
            'whatsapp_orders' => 'integer',
            'avg_time_spent' => 'integer',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
