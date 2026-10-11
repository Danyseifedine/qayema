<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One restaurant's day of writing help: its uses and the tokens they spent
 * (App\Services\Ai\WritingHelpLog writes it).
 */
class WritingHelpDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'day',
        'uses',
        'input_tokens',
        'output_tokens',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'uses' => 'integer',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
        ];
    }

    /**
     * Always a plain date, written as WritingHelpLog's upsert writes it, so
     * a row made one way or the other is the same day to the unique key and
     * to a comparison on any database.
     */
    protected function day(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value): ?CarbonImmutable => $value === null ? null : CarbonImmutable::parse($value)->startOfDay(),
            set: fn (mixed $value): string => CarbonImmutable::parse($value)->toDateString(),
        );
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }
}
