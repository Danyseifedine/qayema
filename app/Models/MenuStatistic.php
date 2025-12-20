<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuStatistic extends Model
{
    protected $fillable = [
        'menu_id',
        'session_id',
        'ip_address',
        'user_agent',
        'referrer',
        'country',
        'device_type',
        'browser',
        'os',
        'viewed_at',
        'left_at',
        'time_spent',
        'page_views',
        'interactions',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
        'left_at' => 'datetime',
        'time_spent' => 'integer',
        'page_views' => 'integer',
        'interactions' => 'array',
    ];

    /**
     * Get the menu that owns the statistic.
     */
    public function menu(): BelongsTo
    {
        return $this->belongsTo(Menu::class);
    }

    /**
     * Calculate time spent when leaving.
     */
    public function calculateTimeSpent(): void
    {
        if ($this->viewed_at && $this->left_at) {
            $this->time_spent = $this->viewed_at->diffInSeconds($this->left_at);
            $this->save();
        }
    }
}
