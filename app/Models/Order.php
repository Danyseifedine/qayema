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

    /**
     * The dishes a new order was asked for but went without, sold out while
     * they sat in the guest's cart, named in the guest's language. Set by
     * OrderPlacer so the guest is told; never stored.
     *
     * @var list<string>
     */
    public array $leftOut = [];

    protected $fillable = [
        'restaurant_id',
        'reference',
        'status',
        'channel',
        'fulfilment',
        'table_id',
        'table_name',
        'currency',
        'exchange_rate',
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
        'owner_updated_at',
        'change_token',
    ];

    protected function casts(): array
    {
        return [
            'restaurant_id' => 'integer',
            'table_id' => 'integer',
            'status' => OrderStatus::class,
            'channel' => OrderChannel::class,
            'fulfilment' => Fulfilment::class,
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'total' => 'decimal:2',
            'exchange_rate' => 'decimal:12',
            'placed_at' => 'datetime',
            'accepted_at' => 'datetime',
            'ready_at' => 'datetime',
            'closed_at' => 'datetime',
            'guest_updated_at' => 'datetime',
            'guest_updates' => 'integer',
            'owner_updated_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Restaurant, $this> */
    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    /**
     * The table it was ordered to; null once that table is removed (`table_name` stays).
     *
     * @return BelongsTo<DiningTable, $this>
     */
    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    /** @return HasMany<OrderItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class)->orderBy('id');
    }

    /**
     * Writes the order's lines in one insert rather than one each, cast as
     * the model casts them (the options as JSON, the prices to two places),
     * in the order given, so `items()` reads them back the same way.
     *
     * @param  array<int, array<string, mixed>>  $lines
     */
    public function addItems(array $lines): void
    {
        $rows = array_map(function (array $line): array {
            $item = $this->items()->make($line);
            $item->updateTimestamps();

            return $item->getAttributes();
        }, $lines);

        // One insert per shape of row: a multi-row insert needs the same columns in each.
        foreach (collect($rows)->groupBy(fn (array $row): string => implode(',', array_keys($row))) as $sameColumns) {
            OrderItem::query()->insert($sameColumns->all());
        }
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
     * Orders to a table (dine-in), which the dashboard keeps on a page of
     * their own.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAtTable(Builder $query): Builder
    {
        return $query->where('fulfilment', Fulfilment::DineIn);
    }

    /**
     * Every other order: delivered, picked up, or from before orders had a
     * kind at all.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAway(Builder $query): Builder
    {
        return $query->where(fn (Builder $query) => $query
            ->whereNull('fulfilment')
            ->orWhere('fulfilment', '!=', Fulfilment::DineIn));
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
