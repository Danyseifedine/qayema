<?php

namespace App\Models;

use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Enums\OrderStatus;
use App\Services\Menu\MenuLanguages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'channel',
        'fulfilment',
        'currency',
        'total',
        'note',
        'guest_name',
        'guest_phone',
        'address',
        'latitude',
        'longitude',
        'client_token',
        'tracking_token',
        'placed_at',
        'accepted_at',
        'ready_at',
        'closed_at',
        'guest_updated_at',
        'guest_updates',
        'change_token',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'status' => OrderStatus::class,
            'channel' => OrderChannel::class,
            'fulfilment' => Fulfilment::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'total' => 'decimal:2',
            'placed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'ready_at' => 'datetime',
            'closed_at' => 'datetime',
            'guest_updated_at' => 'datetime',
            'guest_updates' => 'integer',
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
     * Orders placed in the menu: the real ones the Orders page works
     * through. A WhatsApp order is only a record that WhatsApp was opened.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeInMenu(Builder $query): Builder
    {
        return $query->where('channel', OrderChannel::Menu);
    }

    /**
     * Move the order on, and note when: the guest's tracking page shows
     * when it was accepted and when it was done or called off.
     */
    public function moveTo(OrderStatus $status): void
    {
        $this->status = $status;

        if ($status === OrderStatus::Accepted) {
            $this->accepted_at ??= now();
        }

        if ($status === OrderStatus::Ready) {
            $this->ready_at ??= now();
        }

        $this->closed_at = $status->isClosed() ? ($this->closed_at ?? now()) : null;
        $this->save();
    }

    /** The page a guest follows this order on; null for an order sent to WhatsApp. */
    public function trackingUrl(?string $locale = null): ?string
    {
        if ($this->tracking_token === null) {
            return null;
        }

        return route('public.order.track', array_filter([
            'restaurant' => $this->restaurant->slug,
            'token' => $this->tracking_token,
            // The menu's opening language needs no saying, as on the menu.
            'lang' => $locale === MenuLanguages::default($this->restaurant) ? null : $locale,
        ]));
    }

    /** Where the guest shared their location, on a map; null when they did not. */
    public function mapUrl(): ?string
    {
        if ($this->latitude === null || $this->longitude === null) {
            return null;
        }

        return 'https://www.google.com/maps?q='.$this->latitude.','.$this->longitude;
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
}
