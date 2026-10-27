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
 * A line bought on an offer keeps the offer (`offer_id`, `offer_title`) and
 * its unit price before the discount (`full_price`); null otherwise.
 */
class OrderItem extends Model
{
    /** @use HasFactory<\Database\Factories\OrderItemFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'dish_id',
        'offer_id',
        'offer_title',
        'name',
        'options',
        'unit_price',
        'full_price',
        'quantity',
        'line_total',
        'currency',
    ];

    /** A line from before offers, or made by a factory, reads these as none (strict models). */
    protected $attributes = [
        'offer_id' => null,
        'offer_title' => null,
        'full_price' => null,
        'currency' => null,
    ];

    protected function casts(): array
    {
        return [
            'order_id' => 'integer',
            'dish_id' => 'integer',
            'offer_id' => 'integer',
            'options' => 'array',
            'unit_price' => 'decimal:2',
            'full_price' => 'decimal:2',
            'line_total' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    /** @return BelongsTo<Order, $this> */
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
            ...array_map(fn (array $build): string => $build['name'].': '.self::built($build), $options['builds'] ?? []),
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
            ...array_map(fn (array $build): string => self::built($build), $options['builds'] ?? []),
            ...array_map(fn (array $addon): string => '+ '.$addon['name'], $options['addons'] ?? []),
        ];
    }

    /**
     * A build's parts as read aloud: "½ Margherita + ½ Pepperoni" for a pizza
     * split in two, "Margherita" whole, "2 × Vanilla, Chocolate" for scoops.
     *
     * @param  array{parts?: array<int, array{choice: string, share: int}>}  $build
     */
    public static function built(array $build): string
    {
        $parts = array_values((array) ($build['parts'] ?? []));
        $share = (int) ($parts[0]['share'] ?? 1);

        if ($share > 1) {
            $fraction = ['2' => '½', '3' => '⅓', '4' => '¼', '5' => '⅕', '6' => '⅙', '8' => '⅛'][(string) $share] ?? "1/{$share}";

            return implode(' + ', array_map(fn (array $part): string => $fraction.' '.$part['choice'], $parts));
        }

        $counted = array_count_values(array_column($parts, 'choice'));

        return implode(', ', array_map(
            fn (string $choice, int $count): string => $count > 1 ? "{$count} × {$choice}" : $choice,
            array_keys($counted),
            $counted,
        ));
    }
}
