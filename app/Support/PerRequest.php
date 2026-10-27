<?php

namespace App\Support;

use Closure;

/**
 * A value worked out once per request: kept on the request itself, so the
 * next request (a Livewire update, the next test request) works it out
 * again and never reads a stale copy. For values several parts of one
 * request ask for (a menu badge and a tab badge). A console command or a
 * queue worker has one request for its whole run (a worker lives at most
 * 55 s here), so keep it to values that may be that old.
 */
final class PerRequest
{
    /**
     * @template TValue
     *
     * @param  Closure(): TValue  $work
     * @return TValue
     */
    public static function remember(string $key, Closure $work): mixed
    {
        $attributes = request()->attributes;
        $key = 'per-request:'.$key;

        if (! $attributes->has($key)) {
            $attributes->set($key, $work());
        }

        return $attributes->get($key);
    }
}
