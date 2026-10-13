<?php

namespace App\Services\Menu;

use App\Jobs\PurgeMenuCache;
use App\Models\Restaurant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

use function Illuminate\Support\defer;

/**
 * The menu pages Cloudflare keeps (CacheMenuForGuests), cleared when what
 * they show changes, so guests see a change at once. Changes made in one
 * request are cleared together, after the response: one call to Cloudflare
 * for a save that touched a dish, its options and its photo. A call that
 * fails (Cloudflare's Free plan allows five a minute) is tried again from
 * the queue (PurgeMenuCache); a copy is kept at most
 * CacheMenuForGuests::EDGE_SECONDS anyway.
 *
 * One instance a request (AppServiceProvider). Without a zone and a token
 * (services.cloudflare) nothing is sent: locally and in the tests.
 */
class MenuCache
{
    /** Cloudflare takes at most this many prefixes in one call. */
    public const PREFIXES_A_CALL = 100;

    /** @var array<int, true> */
    private array $restaurants = [];

    /** @var array<string, true> */
    private array $slugs = [];

    private bool $everything = false;

    private bool $scheduled = false;

    /** Clears every copy of this restaurant's menu, under $slugs too (a link it just left). */
    public function forget(Restaurant|int $restaurant, string ...$slugs): void
    {
        $this->restaurants[$restaurant instanceof Restaurant ? $restaurant->getKey() : $restaurant] = true;

        foreach ($slugs as $slug) {
            if ($slug !== '') {
                $this->slugs[$slug] = true;
            }
        }

        $this->schedule();
    }

    /** Clears every menu: a change that reaches all of them (a package, a design). */
    public function forgetAll(): void
    {
        $this->everything = true;
        $this->schedule();
    }

    /** Whether Cloudflare can be asked at all. */
    public static function isOn(): bool
    {
        return filled(config('services.cloudflare.zone_id')) && filled(config('services.cloudflare.api_token'));
    }

    /**
     * Asks Cloudflare to drop what $body names; false when it would not (a
     * refusal, its limit, no answer), for the caller to try again.
     *
     * @param  array<string, mixed>  $body  `purge_everything` or `prefixes`
     */
    public static function send(array $body): bool
    {
        try {
            return Http::withToken((string) config('services.cloudflare.api_token'))
                ->acceptJson()
                ->timeout(5)
                ->post('https://api.cloudflare.com/client/v4/zones/'.config('services.cloudflare.zone_id').'/purge_cache', $body)
                ->successful();
        } catch (Throwable) {
            return false;
        }
    }

    private function schedule(): void
    {
        if ($this->scheduled || ! self::isOn()) {
            return;
        }

        $this->scheduled = true;
        defer(fn () => $this->flush());
    }

    /** What this request changed, in as few calls as Cloudflare allows. */
    private function flush(): void
    {
        foreach ($this->bodies() as $body) {
            if (! self::send($body)) {
                // Seen at once in Grafana, before the retries run out.
                Log::warning('Menu cache purge refused: trying again from the queue.', ['body' => $body]);
                PurgeMenuCache::dispatch($body);
            }
        }

        $this->restaurants = $this->slugs = [];
        $this->everything = $this->scheduled = false;
    }

    /** @return list<array<string, mixed>> */
    private function bodies(): array
    {
        if ($this->everything) {
            return [['purge_everything' => true]];
        }

        $slugs = Restaurant::query()->whereKey(array_keys($this->restaurants))->pluck('slug')->all();
        $host = (string) parse_url((string) config('app.url'), PHP_URL_HOST);
        // A prefix is the host and the path, no scheme: it covers every
        // address under it, whatever follows the ? (each language, table, scan).
        $prefixes = array_map(fn (string $slug): string => $host.'/'.$slug, array_values(array_unique([...$slugs, ...array_keys($this->slugs)])));

        return array_map(fn (array $chunk): array => ['prefixes' => $chunk], array_chunk($prefixes, self::PREFIXES_A_CALL));
    }
}
