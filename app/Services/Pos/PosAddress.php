<?php

namespace App\Services\Pos;

/**
 * The addresses Qayema calls for a POS: its own, as the owner typed it, and
 * the photos its catalog names. The owner chooses them, so in production
 * only a public https address is called, never one on Qayema's own network
 * (services.pos.allow_private lifts that for local development).
 */
class PosAddress
{
    /**
     * The POS address as Qayema keeps it: scheme, host, port and path (a POS
     * may live under one), no query or trailing slash. Null when it is not an
     * address Qayema may call.
     */
    public static function normalize(string $typed): ?string
    {
        $typed = trim($typed);

        if ($typed !== '' && ! preg_match('#^[a-z][a-z0-9+.-]*://#i', $typed)) {
            $typed = 'https://'.$typed;
        }

        $parts = parse_url($typed);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host']) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        $address = strtolower($parts['scheme']).'://'.strtolower($parts['host']).(isset($parts['port']) ? ':'.$parts['port'] : '').rtrim($parts['path'] ?? '', '/');

        return self::mayCall($address) ? $address : null;
    }

    /**
     * Whether Qayema may call this address: http(s), and in production https
     * to a host that resolves only to public addresses.
     */
    public static function mayCall(string $url): bool
    {
        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        $host = (string) parse_url($url, PHP_URL_HOST);

        if ($host === '' || ! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if (self::allowsPrivate()) {
            return true;
        }

        return $scheme === 'https' && self::isPublic($host);
    }

    private static function allowsPrivate(): bool
    {
        return ! app()->isProduction() || (bool) config('services.pos.allow_private');
    }

    /** Every address the host resolves to is a public one. */
    private static function isPublic(string $host): bool
    {
        $host = trim($host, '[]');
        $ips = filter_var($host, FILTER_VALIDATE_IP) ? [$host] : array_merge(
            gethostbynamel($host) ?: [],
            array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
        );

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
                return false;
            }
        }

        return true;
    }
}
