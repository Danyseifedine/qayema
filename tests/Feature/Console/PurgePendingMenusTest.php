<?php

namespace Tests\Feature\Console;

use App\Services\Menu\MenuCache;
use App\Services\Menu\PendingPurges;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * `menus:purge-pending`: the purges Cloudflare refused (PendingPurges), sent
 * every minute in at most the five calls its Free plan allows, the longest
 * waiting first; what is still refused waits for the next minute, and after
 * 15 minutes is given up loudly.
 */
class PurgePendingMenusTest extends TestCase
{
    private const PURGE = 'https://api.cloudflare.com/client/v4/zones/zone-1/purge_cache';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.cloudflare.zone_id' => 'zone-1',
            'services.cloudflare.api_token' => 'purge-only-token',
        ]);
        $this->freezeTime();
    }

    /** @return list<array<string, mixed>> what was sent to Cloudflare */
    private function purges(): array
    {
        return Http::recorded()->map(fn (array $pair): array => $pair[0]->data())->values()->all();
    }

    /** @param  list<string>  $slugs */
    private function refused(array $slugs): void
    {
        PendingPurges::add(['prefixes' => array_map(fn (string $slug): string => 'qayema.test/'.$slug, $slugs)]);
    }

    public function test_it_runs_every_minute(): void
    {
        $event = collect(app(Schedule::class)->events())->first(fn ($event) => str_contains((string) $event->command, 'menus:purge-pending'));

        $this->assertNotNull($event, 'menus:purge-pending is not on the schedule');
        $this->assertSame('* * * * *', $event->expression);
    }

    public function test_what_was_refused_is_sent_and_forgotten(): void
    {
        Http::fake([self::PURGE => Http::response(['success' => true])]);
        $this->refused(['olive', 'cedar']);

        $this->artisan('menus:purge-pending')->assertSuccessful()->expectsOutputToContain('Cleared 2');

        $this->assertSame([['prefixes' => ['qayema.test/olive', 'qayema.test/cedar']]], $this->purges());
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer purge-only-token'));
        $this->assertSame([], PendingPurges::all());
    }

    public function test_a_prefix_refused_twice_is_sent_once(): void
    {
        Http::fake([self::PURGE => Http::response(['success' => true])]);
        $this->refused(['olive']);
        $this->travel(10)->seconds();
        $this->refused(['olive']);

        $this->assertSame(['prefixes|qayema.test/olive' => now()->subSeconds(10)->getTimestamp()], PendingPurges::all());

        $this->artisan('menus:purge-pending')->assertSuccessful();

        $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
    }

    public function test_a_minute_sends_at_most_five_calls_the_longest_waiting_first(): void
    {
        Http::fake([self::PURGE => Http::response(['success' => true])]);
        $this->refused(array_map(fn (int $i): string => 'shop-'.$i, range(1, PendingPurges::CALLS_A_RUN * MenuCache::PREFIXES_A_CALL)));
        $this->travel(1)->minutes();
        $this->refused(['last']);

        $this->artisan('menus:purge-pending')->assertSuccessful();

        $this->assertCount(PendingPurges::CALLS_A_RUN, $this->purges());
        $this->assertCount(MenuCache::PREFIXES_A_CALL, $this->purges()[0]['prefixes']);
        $this->assertSame(['prefixes|qayema.test/last'], array_keys(PendingPurges::all()));
    }

    public function test_a_refusal_stops_the_minute_and_keeps_the_rest_with_when_it_was_first_refused(): void
    {
        $this->refused(array_map(fn (int $i): string => 'shop-'.$i, range(1, 2 * MenuCache::PREFIXES_A_CALL)));
        $since = now()->getTimestamp();
        Http::fake([self::PURGE => Http::sequence()->push(['success' => true])->push(['success' => false], 429)]);
        $this->travel(5)->minutes();

        $this->artisan('menus:purge-pending')->assertSuccessful()->expectsOutputToContain('Cleared '.MenuCache::PREFIXES_A_CALL);

        $this->assertCount(2, $this->purges());
        $pending = PendingPurges::all();
        $this->assertCount(MenuCache::PREFIXES_A_CALL, $pending);
        $this->assertSame([$since], array_values(array_unique($pending)));
    }

    public function test_every_menu_is_one_call_for_the_host_that_covers_the_prefixes_too(): void
    {
        Http::fake([self::PURGE => Http::response(['success' => true])]);
        $this->refused(['olive']);
        PendingPurges::add(['hosts' => ['qayema.test']]);

        $this->artisan('menus:purge-pending')->assertSuccessful()->expectsOutputToContain('Cleared 2');

        $this->assertSame([['hosts' => ['qayema.test']]], $this->purges());
        $this->assertSame([], PendingPurges::all());
    }

    public function test_a_purge_still_refused_after_fifteen_minutes_is_given_up_loudly(): void
    {
        Http::fake([self::PURGE => Http::response([], 500)]);
        Log::spy();
        $this->refused(['olive']);
        $this->travel(PendingPurges::GIVE_UP_SECONDS - 60)->seconds();
        $this->refused(['cedar']);
        $this->travel(60)->seconds();

        $this->artisan('menus:purge-pending')->assertSuccessful();

        Log::shouldHaveReceived('error')->with('Menu cache purge failed: guests may see an old menu until it expires.', ['purges' => ['prefixes|qayema.test/olive']])->once();
        $this->assertSame(['prefixes|qayema.test/cedar'], array_keys(PendingPurges::all()));
    }

    public function test_nothing_waiting_sends_nothing(): void
    {
        Http::fake();

        $this->artisan('menus:purge-pending')->assertSuccessful()->doesntExpectOutputToContain('Cleared');

        Http::assertNothingSent();
    }

    public function test_without_cloudflare_set_up_nothing_is_sent_and_nothing_lost(): void
    {
        Http::fake();
        $this->refused(['olive']);
        config(['services.cloudflare.zone_id' => null]);

        $this->artisan('menus:purge-pending')->assertSuccessful();

        Http::assertNothingSent();
        $this->assertSame(['prefixes|qayema.test/olive'], array_keys(PendingPurges::all()));
    }
}
