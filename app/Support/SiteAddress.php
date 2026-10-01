<?php

namespace App\Support;

/**
 * The site's one address: APP_URL without a "www.", so qayema.com and
 * www.qayema.com never count as two sites, even if APP_URL names www.
 * Search engines are pointed at it (AppServiceProvider) and visits to the
 * www. address are sent to it (RedirectToMainAddress).
 */
class SiteAddress
{
    /** e.g. https://qayema.com, with no trailing slash. */
    public static function root(): string
    {
        $url = rtrim((string) config('app.url'), '/');
        $host = (string) parse_url($url, PHP_URL_HOST);

        return str_starts_with($host, 'www.')
            ? preg_replace('#//www\.#', '//', $url, 1)
            : $url;
    }

    /** e.g. qayema.com */
    public static function host(): string
    {
        return (string) parse_url(self::root(), PHP_URL_HOST);
    }
}
