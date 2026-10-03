<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One line of an order. `name`, `options` and `unit_price` are what they
 * were when the order was placed, not what the dish says today. `options`
 * holds the guest's choices, in their language:
 * `{variants: [{name, choice, price}], addons: [{name, price}]}`, or null.
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'dish_id',
        'name',
        'options',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'dish_id' => 'integer',
            'options' => 'array',
            'unit_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * The guest's choices as short lines: "Size: Large", then "+ Extra cheese".
     *
     * @return array<int, string>
     */
    public function choices(): array
    {
        $options = (array) $this->options;

        return [
            ...array_map(fn (array $variant): string => $variant['name'].': '.$variant['choice'], $options['variants'] ?? []),
            ...array_map(fn (array $addon): string => '+ '.$addon['name'], $options['addons'] ?? []),
        ];
    }

    /**
     * Only what the guest picked, as their cart showed it: "Large", "Hot",
     * "+ Extra cheese". For the guest's own order page, where they know what
     * each one was a choice of.
     *
     * @return array<int, string>
     */
    public function picks(): array
    {
        $options = (array) $this->options;

        return [
            ...array_map(fn (array $variant): string => $variant['choice'], $options['variants'] ?? []),
            ...array_map(fn (array $addon): string => '+ '.$addon['name'], $options['addons'] ?? []),
        ];
    }
}
