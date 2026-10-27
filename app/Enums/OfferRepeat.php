<?php

namespace App\Enums;

/**
 * Which days an offer runs on, inside its dates: every day, some weekdays,
 * or some days of the month (App\Services\Offers\OfferSchedule).
 */
enum OfferRepeat: string
{
    case Always = 'always';
    case Weekly = 'weekly';
    case Monthly = 'monthly';

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
