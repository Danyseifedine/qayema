<?php

namespace App\Enums;

/**
 * Why a coin ledger row exists. Credits are positive, spends negative — the
 * type records the reason, and pairs with `reference` to make a row idempotent.
 */
enum CoinTransactionType: string
{
    /** Bought with real money through Paddle. `reference` is the transaction id. */
    case Purchase = 'purchase';

    /** Spent in-app (e.g. unlocking a template). `reference` is what was bought. */
    case Spend = 'spend';

    /** Handed out from the admin panel. */
    case AdminGrant = 'admin_grant';

    /** Taken back after the Paddle purchase that paid for them was refunded. */
    case Refund = 'refund';

    public function isCredit(): bool
    {
        return in_array($this, [self::Purchase, self::AdminGrant], true);
    }

    public function label(): string
    {
        return match ($this) {
            self::Purchase => __('coins.purchase'),
            self::Spend => __('coins.spend'),
            self::AdminGrant => __('coins.admin_grant'),
            self::Refund => __('coins.refund'),
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type): array => [$type->value => $type->label()])
            ->all();
    }
}
