<?php

namespace Tests\Feature\Console;

use App\Enums\Feature;
use App\Models\FeatureGrant;
use App\Models\Offer;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * `menus:forget-ended` (hourly) and `menus:forget-all` (deploy.sh): what
 * changes with the clock or with a release reaches the menus Cloudflare
 * keeps (MenuCache).
 */
class ForgetEndedMenusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://qayema.test',
            'services.cloudflare.zone_id' => 'zone-1',
            'services.cloudflare.api_token' => 'purge-only-token',
        ]);
        Http::swap(new HttpFactory);
        Http::fake(fn () => Http::response(['success' => true]));
        Queue::fake();
    }

    /**
     * What was asked of Cloudflare once the command's deferred work ran.
     *
     * @return list<array<string, mixed>>
     */
    private function purged(): array
    {
        app(DeferredCallbackCollection::class)->invoke();

        return Http::recorded()->map(fn (array $pair): array => $pair[0]->data())->values()->all();
    }

    /** A restaurant on a package that started a month ago, unless told otherwise. */
    private function restaurant(string $slug, array $attributes = []): Restaurant
    {
        return Restaurant::factory()->create(['slug' => $slug, 'package_started_at' => now()->subMonth(), ...$attributes]);
    }

    public function test_menus_whose_package_started_menu_went_offline_or_grant_ended_in_the_last_hour_are_cleared(): void
    {
        $this->restaurant('offline', ['package_ends_at' => now()->subDays(3)->subMinutes(30)]);
        $this->restaurant('just-ended', ['package_ends_at' => now()->subMinutes(30)]);
        $this->restaurant('started', ['package_started_at' => now()->subMinutes(10)]);
        $granted = $this->restaurant('granted');
        FeatureGrant::query()->create(['restaurant_id' => $granted->id, 'feature' => Feature::Analytics, 'value' => 1, 'source' => 'admin', 'ends_at' => now()->subMinutes(5)]);
        $this->restaurant('long-ago', ['package_ends_at' => now()->subDays(3)->subHours(3)]);
        $this->restaurant('still-on', ['package_ends_at' => now()->addDay()]);
        Http::swap(new HttpFactory);
        Http::fake(fn () => Http::response(['success' => true]));
        $this->app->forgetScopedInstances();

        $this->artisan('menus:forget-ended')->assertSuccessful()->expectsOutputToContain('Clearing 3 menus');

        $purges = $this->purged();
        $this->assertCount(1, $purges);
        $this->assertEqualsCanonicalizing(
            ['qayema.test/offline', 'qayema.test/started', 'qayema.test/granted'],
            $purges[0]['prefixes'],
        );
    }

    public function test_menus_whose_offer_started_or_ended_in_the_last_hour_are_cleared(): void
    {
        Offer::factory()->create(['restaurant_id' => $this->restaurant('offer-started')->id, 'starts_at' => now()->subMinutes(20)]);
        Offer::factory()->create(['restaurant_id' => $this->restaurant('offer-ended')->id, 'ends_at' => now()->subMinutes(40)]);
        Offer::factory()->create(['restaurant_id' => $this->restaurant('offer-later')->id, 'starts_at' => now()->addHour()]);
        $this->app->forgetScopedInstances();
        Http::swap(new HttpFactory);
        Http::fake(fn () => Http::response(['success' => true]));

        $this->artisan('menus:forget-ended')->assertSuccessful()->expectsOutputToContain('Clearing 2 menus');

        $this->assertEqualsCanonicalizing(['qayema.test/offer-started', 'qayema.test/offer-ended'], $this->purged()[0]['prefixes']);
    }

    public function test_nothing_changed_sends_nothing(): void
    {
        $this->restaurant('quiet', ['package_ends_at' => now()->addMonth()]);
        $this->app->forgetScopedInstances();
        Http::swap(new HttpFactory);
        Http::fake();

        $this->artisan('menus:forget-ended')->assertSuccessful()->expectsOutputToContain('Clearing 0 menus');

        $this->assertSame([], $this->purged());
    }

    public function test_a_wider_window_can_be_passed(): void
    {
        $this->restaurant('yesterday', ['package_ends_at' => now()->subDays(3)->subHours(20)]);
        $this->app->forgetScopedInstances();
        Http::swap(new HttpFactory);
        Http::fake(fn () => Http::response(['success' => true]));

        $this->artisan('menus:forget-ended', ['--minutes' => 24 * 60])->assertSuccessful();

        $this->assertSame([['prefixes' => ['qayema.test/yesterday']]], $this->purged());
    }

    public function test_a_release_clears_every_menu(): void
    {
        $this->artisan('menus:forget-all')->assertSuccessful()->expectsOutputToContain('Clearing every cached menu');

        $this->assertSame([['hosts' => ['qayema.test']]], $this->purged());
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer purge-only-token'));
    }

    public function test_without_cloudflare_set_up_a_release_clears_nothing_and_says_so(): void
    {
        config(['services.cloudflare.api_token' => null]);

        $this->artisan('menus:forget-all')->assertSuccessful()->expectsOutputToContain('Cloudflare is not set up');

        $this->assertSame([], $this->purged());
    }
}
