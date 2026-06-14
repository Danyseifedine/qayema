<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Trusted IPs
    |--------------------------------------------------------------------------
    |
    | IPs that AbuseGuard will never rate-ban or block — e.g. office, monitoring,
    | or admin egress IPs — so a shared NAT can't lock you out of your own app.
    | Provide a comma-separated list via the SECURITY_TRUSTED_IPS env variable.
    |
    */

    'trusted_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('SECURITY_TRUSTED_IPS', '')),
    ))),

];
