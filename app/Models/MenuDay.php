<?php

namespace App\Models;

use App\Support\HyperLogLog;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One menu's analytics for one of its days, added up from the raw visits and
 * guest actions before those are deleted (App\Services\Analytics\MenuDays).
 */
class MenuDay extends Model
{
    /** @use HasFactory<\Database\Factories\MenuDayFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'day',
        'views',
        'qr_scans',
        'visitors',
        'carted',
        'hours',
        'languages',
        'devices',
        'browsers',
        'systems',
        'actions',
        'dishes_added',
        'categories_opened',
        'searches',
        'missed_searches',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'views' => 'integer',
            'qr_scans' => 'integer',
            'hours' => 'array',
            'languages' => 'array',
            'devices' => 'array',
            'browsers' => 'array',
            'systems' => 'array',
            'actions' => 'array',
            'dishes_added' => 'array',
            'categories_opened' => 'array',
            'searches' => 'array',
            'missed_searches' => 'array',
        ];
    }

    /**
     * The day, stored as a plain date ("2026-10-13") on every database: a
     * date cast writes "2026-10-13 00:00:00", which SQLite (the tests)
     * compares as text unlike MySQL's date column, so a wrong `<=` would pass.
     *
     * @return Attribute<CarbonImmutable, CarbonInterface|string>
     */
    protected function day(): Attribute
    {
        return Attribute::make(
            get: fn (string $value): CarbonImmutable => CarbonImmutable::parse($value)->startOfDay(),
            set: fn (CarbonInterface|string $value): string => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    public function visitorSketch(): HyperLogLog
    {
        return HyperLogLog::fromStored($this->visitors);
    }

    public function cartedSketch(): HyperLogLog
    {
        return HyperLogLog::fromStored($this->carted);
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
