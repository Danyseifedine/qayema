<?php

namespace App\Services\Menu;

/**
 * What the public menu's own requests carry in place of a CSRF token (an
 * order, its change, the guests' actions): a guest has no session to keep
 * one in, so the page is handed a token signed with the app key and the
 * time, checked by VerifyMenuToken. It proves a request comes from a menu
 * page opened lately, without anything stored on the server, so a page
 * served from a cache works as well.
 */
final class MenuToken
{
    /** The header the menu's scripts send it in. */
    public const HEADER = 'X-Menu-Token';

    /** How long a page may stay open and still order, in seconds: a day. */
    public const LIFETIME = 86400;

    /** Seconds a token may be dated ahead, for a clock a little fast. */
    private const SKEW = 60;

    public static function issue(): string
    {
        $at = now()->getTimestamp();

        return $at.'.'.self::sign($at);
    }

    public static function isValid(?string $token): bool
    {
        if ($token === null || preg_match('/^(\d{1,12})\.([a-f0-9]{64})$/', $token, $parts) !== 1) {
            return false;
        }

        $age = now()->getTimestamp() - (int) $parts[1];

        return $age >= -self::SKEW && $age <= self::LIFETIME && hash_equals(self::sign((int) $parts[1]), $parts[2]);
    }

    private static function sign(int $at): string
    {
        return hash_hmac('sha256', "menu:{$at}", (string) config('app.key'));
    }
}
