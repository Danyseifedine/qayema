<?php

namespace App\Models;

use App\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * What a guest asked for, as they asked for it.
 *
 * An order is a record, not a live document: its lines carry their own names
 * and prices, so nothing an owner does to the menu afterwards can change what
 * was ordered.
 */
class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'restaurant_id',
        'reference',
        'status',
        'currency',
        'total',
        'note',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * A short code a guest can read out loud.
     *
     * Digits and the letters that cannot be misheard: no O/0, I/1, or S/5.
     */
    public static function newReference(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRTUVWXYZ23456789';

        do {
            $reference = '';

            for ($i = 0; $i < 6; $i++) {
                $reference .= $alphabet[random_int(0, strlen($alphabet) - 1)];
            }
        } while (self::query()->where('reference', $reference)->exists());

        return $reference;
    }

    public function totalQuantity(): int
    {
        return (int) $this->items->sum('quantity');
    }

    /** @return string the human title used in lists and emails */
    public function title(): string
    {
        return Str::upper($this->reference);
    }
}
