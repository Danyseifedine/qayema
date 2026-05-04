<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuStatistic extends Model
{
    protected $fillable = [
        'menu_id',
        'session_id',
        'device_type',
        'browser',
        'os',
        'viewed_at',
        'time_spent',
        'page_views',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'time_spent' => 'integer',
        'page_views' => 'integer',
    ];

    /**
     * Get the menu that owns the statistic.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }
}
