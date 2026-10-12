<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted IPs
    |--------------------------------------------------------------------------
    |
    | IPs that AbuseGuard will never rate-ban or block (e.g. office, monitoring,
    | or admin egress IPs), so a shared NAT can't lock you out of your own app.
    | Provide a comma-separated list via the SECURITY_TRUSTED_IPS env variable.
    |
    */

    'trusted_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SECURITY_TRUSTED_IPS', '')),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Trusted proxies
    |--------------------------------------------------------------------------
    |
    | The reverse proxies in front of the app (Cloudflare in production), as
    | comma-separated IPs/CIDRs or * for all: their X-Forwarded-For names the
    | guest, so rate limits and AbuseGuard see real IPs. Read here, not in
    | bootstrap/app.php, so a cached config (php artisan optimize) keeps it:
    | once config is cached, .env is no longer read. AppServiceProvider
    | applies it.
    |
    */

    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
