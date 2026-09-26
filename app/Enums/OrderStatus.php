<?php

namespace App\Enums;

/**
 * Where an order has got to.
 *
 * Deliberately three states. An order arrives, it is dealt with, or it is
 * called off — anything finer (accepted, preparing, ready) is a kitchen
 * workflow this product does not run.
 */
enum OrderStatus: string
{
    case Placed = 'placed';
    case Done = 'done';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Placed => __('orders.placed'),
            self::Done => __('orders.done'),
            self::Cancelled => __('orders.cancelled'),
        };
    }

    /** True while the order still needs the owner's attention. */
    public function isOpen(): bool
    {
        return $this === self::Placed;
    }

    /**
     * @return array<string, string> value => label, for admin selects.
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $status): array => [$status->value => $status->label()])
            ->all();
    }
}
