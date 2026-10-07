<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A table in the restaurant, with a QR code of its own: scanned, it opens the
 * menu knowing which table the guest sits at, so they can order to it.
 *
 * `code` is what the QR code carries. It is random and unguessable, so a
 * table cannot be ordered to from anywhere but in front of its code; a new
 * one (newCode()) retires the old printed card.
 */
class DiningTable extends Model
{
    /** @use HasFactory<\Database\Factories\DiningTableFactory> */
    use HasFactory;

    /** Letters and digits that cannot be misread from a printed card. */
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyz23456789';

    private const CODE_LENGTH = 10;

    protected $fillable = [
        'restaurant_id',
        'name',
        'code',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $table): void {
            $table->code ??= self::freshCode();
        });
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    /** What this table's QR code opens: the menu, at this table, counted as a scan. */
    public function menuUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$this->restaurant->slug.'?'.http_build_query([
            'table' => $this->code,
            'qr' => 1,
        ]);
    }

    /** Give the table a new code; the old QR code stops opening it. */
    public function newCode(): void
    {
        $this->forceFill(['code' => self::freshCode()])->save();
    }

    public static function freshCode(): string
    {
        do {
            $code = '';

            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
            }
        } while (self::query()->where('code', $code)->exists());

        return $code;
    }
}
