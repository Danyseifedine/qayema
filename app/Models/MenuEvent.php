<?php

namespace App\Models;

use App\Enums\MenuEventType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One thing a guest did on a public menu. See the menu_events migration. */
class MenuEvent extends Model
{
    /** @use HasFactory<\Database\Factories\MenuEventFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'restaurant_id',
        'session_id',
        'type',
        'dish_id',
        'category_id',
        'value',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => MenuEventType::class,
            'occurred_at' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
