<?php

namespace App\Models;

use App\Services\Global\Package;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RestaurantFeature extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'feature_id',
        'value',
        'source',
        'starts_at',
        'ends_at',
        'payment_id',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $flush = fn (self $grant) => Package::flush($grant->restaurant_id);

        static::saved($flush);
        static::deleted($flush);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function feature(): BelongsTo
    {
        return $this->belongsTo(Feature::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }
}
