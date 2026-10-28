<?php

namespace App\Models;

use App\Models\Concerns\ClearsMenuCache;
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
    use ClearsMenuCache, HasFactory;

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

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    /** What this table's QR code opens: the menu, at this table, counted as a scan. */
    public function menuUrl(): string
    {
        return $this->restaurant->publicUrl().'?'.http_build_query([
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
        return self::freshCodes(1)[0];
    }

    /**
     * `$count` new codes, none taken: checked all at once, so adding twenty
     * tables is one look rather than twenty.
     *
     * @return list<string>
     */
    public static function freshCodes(int $count): array
    {
        $codes = [];

        while (count($codes) < $count) {
            $wanted = [];
            while (count($wanted) < $count - count($codes)) {
                $code = self::randomCode();
                if (! in_array($code, $codes, true)) {
                    $wanted[$code] = $code;
                }
            }

            $taken = self::query()->whereIn('code', array_values($wanted))->pluck('code')->all();
            $codes = [...$codes, ...array_values(array_diff($wanted, $taken))];
        }

        return $codes;
    }

    private static function randomCode(): string
    {
        $code = '';

        for ($i = 0; $i < self::CODE_LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }

        return $code;
    }
}
