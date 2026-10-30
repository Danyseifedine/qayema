<?php

namespace App\Services\Pos;

/**
 * How a POS signs what it sends to Qayema: an HMAC of the timestamp and the
 * body with the link's signing secret, in X-Pos-Signature ("sha256=..."),
 * the timestamp in X-Pos-Timestamp. A signature older than five minutes
 * (or that far in the future) is refused, so a copied call cannot be sent
 * again later.
 */
class PosSignature
{
    public const MAX_AGE_SECONDS = 300;

    public static function sign(string $secret, int $timestamp, string $body): string
    {
        return 'sha256='.hash_hmac('sha256', $timestamp.'.'.$body, $secret);
    }

    public static function verify(string $secret, ?string $timestamp, ?string $signature, string $body, ?int $now = null): bool
    {
        if ($timestamp === null || $signature === null || ! ctype_digit($timestamp)) {
            return false;
        }

        if (abs(($now ?? time()) - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return false;
        }

        return hash_equals(self::sign($secret, (int) $timestamp, $body), $signature);
    }
}
