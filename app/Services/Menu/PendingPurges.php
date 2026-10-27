<?php

namespace App\Services\Menu;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * The Cloudflare purges it refused (MenuCache), kept in the cache (Redis in
 * production) until `menus:purge-pending` sends them, once a minute, in at
 * most the calls Cloudflare's Free plan allows a minute. A queue of retries
 * spent that same budget again and again; one set of what is still owed
 * never sends a prefix twice and never more calls than a minute allows.
 *
 * Kept as one cache key, `<kind>|<value>` (`prefixes|qayema.com/olive`,
 * `hosts|qayema.com`) to when it was first refused, changed under a lock so
 * two requests adding at once lose nothing. A purge still refused after
 * GIVE_UP_SECONDS is dropped and logged as an error: the copies expire on
 * their own within CacheMenuForGuests::EDGE_SECONDS.
 */
final class PendingPurges
{
    public const KEY = 'menu-cache:pending-purges';

    /** Cloudflare's Free plan allows five purge calls a minute. */
    public const CALLS_A_RUN = 5;

    public const GIVE_UP_SECONDS = 900;

    /**
     * Keeps what $body names until it is sent.
     *
     * @param  array<string, mixed>  $body  `prefixes` or `hosts`, as MenuCache sends it
     */
    public static function add(array $body): void
    {
        $since = array_fill_keys(self::entries($body), now()->getTimestamp());

        self::change(fn (array $pending): array => self::merge($pending, $since));
    }

    /** @return array<string, int> what is waiting, each with when it was first refused */
    public static function all(): array
    {
        return Cache::get(self::KEY, []);
    }

    /**
     * Sends what is waiting, the longest waiting first, in at most
     * CALLS_A_RUN calls; stops at the first refusal and keeps what was not
     * cleared for the next run.
     *
     * @return int how many prefixes or hosts were cleared
     */
    public static function send(): int
    {
        $cleared = 0;
        $batches = self::batches(self::take());

        foreach ($batches as $i => [$body, $entries]) {
            if (! MenuCache::send($body)) {
                self::putBack(array_merge(...array_column(array_slice($batches, $i), 1)));

                break;
            }

            $cleared += count($entries);
        }

        return $cleared;
    }

    /**
     * Removes from the set what this run will send: a host's purge with
     * every prefix on that host (it covers them), or else the prefixes
     * that fit in this run's calls.
     *
     * @return array<string, int>
     */
    private static function take(): array
    {
        $taken = [];

        self::change(function (array $pending) use (&$taken): array {
            asort($pending);
            $hosts = array_map(fn (string $entry): string => substr($entry, strlen('hosts|')), self::ofKind($pending, 'hosts'));

            $taken = $hosts === []
                ? array_slice($pending, 0, self::CALLS_A_RUN * MenuCache::PREFIXES_A_CALL, true)
                : array_filter($pending, fn (string $entry): bool => str_starts_with($entry, 'hosts|')
                    || in_array(explode('/', substr($entry, strlen('prefixes|')))[0], $hosts, true), ARRAY_FILTER_USE_KEY);

            return array_diff_key($pending, $taken);
        });

        return $taken;
    }

    /**
     * The calls that clear $taken, each with the entries it clears.
     *
     * @param  array<string, int>  $taken
     * @return list<array{0: array<string, list<string>>, 1: array<string, int>}>
     */
    private static function batches(array $taken): array
    {
        $hosts = self::ofKind($taken, 'hosts');

        if ($hosts !== []) {
            return [[['hosts' => self::values($hosts)], $taken]];
        }

        return array_map(
            fn (array $chunk): array => [['prefixes' => self::values(array_keys($chunk))], $chunk],
            array_chunk($taken, MenuCache::PREFIXES_A_CALL, true),
        );
    }

    /**
     * Back into the set, but for what has waited too long: that is dropped,
     * loudly.
     *
     * @param  array<string, int>  $entries
     */
    private static function putBack(array $entries): void
    {
        $tooOld = now()->getTimestamp() - self::GIVE_UP_SECONDS;
        $givenUp = array_filter($entries, fn (int $since): bool => $since <= $tooOld);

        if ($givenUp !== []) {
            Log::error('Menu cache purge failed: guests may see an old menu until it expires.', ['purges' => array_keys($givenUp)]);
        }

        self::change(fn (array $pending): array => self::merge($pending, array_diff_key($entries, $givenUp)));
    }

    /**
     * @param  array<string, int>  $pending
     * @param  array<string, int>  $more
     * @return array<string, int>
     */
    private static function merge(array $pending, array $more): array
    {
        foreach ($more as $entry => $since) {
            $pending[$entry] = min($pending[$entry] ?? $since, $since);
        }

        return $pending;
    }

    /** @param  callable(array<string, int>): array<string, int>  $change */
    private static function change(callable $change): void
    {
        Cache::lock(self::KEY.':lock', 10)->block(5, function () use ($change): void {
            $pending = $change(self::all());

            $pending === [] ? Cache::forget(self::KEY) : Cache::forever(self::KEY, $pending);
        });
    }

    /**
     * @param  array<string, mixed>  $body
     * @return list<string>
     */
    private static function entries(array $body): array
    {
        $entries = [];

        foreach (['prefixes', 'hosts'] as $kind) {
            foreach ((array) ($body[$kind] ?? []) as $value) {
                $entries[] = $kind.'|'.$value;
            }
        }

        return $entries;
    }

    /**
     * @param  array<string, int>  $entries
     * @return list<string>
     */
    private static function ofKind(array $entries, string $kind): array
    {
        return array_values(array_filter(array_keys($entries), fn (string $entry): bool => str_starts_with($entry, $kind.'|')));
    }

    /**
     * @param  list<string>  $entries
     * @return list<string>
     */
    private static function values(array $entries): array
    {
        return array_map(fn (string $entry): string => explode('|', $entry, 2)[1], $entries);
    }
}
