<?php

namespace Tests\Feature\Menu;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Dish;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use App\Models\Template;
use App\Services\Menu\DisplayOrder;
use App\Services\Menu\MenuCache;
use App\Services\Menu\PendingPurges;
use Closure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Defer\DeferredCallbackCollection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Services\Menu\MenuCache: a change to what a menu shows clears its
 * copies from Cloudflare, once per request, after the response.
 */
class MenuCacheTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const PURGE = 'https://api.cloudflare.com/client/v4/zones/zone-1/purge_cache';

    /** What the pretend Cloudflare answers: success unless a test says otherwise. */
    private ?Closure $cloudflare = null;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://qayema.test',
            'services.cloudflare.zone_id' => 'zone-1',
            'services.cloudflare.api_token' => 'purge-only-token',
        ]);
        $this->freshCloudflare();
    }

    /** A pretend Cloudflare with nothing recorded yet, and a new request's MenuCache. */
    private function freshCloudflare(?Closure $answer = null): void
    {
        $this->cloudflare = $answer;
        Http::swap(new HttpFactory);
        Http::fake(fn () => $this->cloudflare !== null ? ($this->cloudflare)() : Http::response(['success' => true]));
        $this->app->forgetScopedInstances();
    }

    /** What the request would do after its response. */
    private function respond(): void
    {
        app(DeferredCallbackCollection::class)->invoke();
    }

    /** @return list<array<string, mixed>> what was sent to Cloudflare */
    private function purges(): array
    {
        return Http::recorded()->map(fn (array $pair): array => $pair[0]->data())->values()->all();
    }

    private function quietly(callable $setUp): mixed
    {
        $made = $setUp();
        $this->respond();
        $this->freshCloudflare();

        return $made;
    }

    public function test_a_dish_saved_clears_its_menu_with_every_address_under_it(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        Dish::factory()->for($shop)->create();
        $this->respond();

        $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
        Http::assertSent(fn (Request $request) => $request->hasHeader('Authorization', 'Bearer purge-only-token'));
    }

    public function test_a_request_that_changes_many_things_clears_once(): void
    {
        [$shop, $dish] = $this->quietly(function () {
            $shop = $this->owner(['slug' => 'olive']);

            return [$shop, Dish::factory()->for($shop)->create()];
        });

        $dish->update(['price' => '9.00']);
        Category::factory()->for($shop)->create();
        RestaurantSocialLink::factory()->for($shop)->create();
        $shop->update(['description' => ['en' => 'New words']]);
        $this->respond();

        $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
    }

    public function test_every_row_a_menu_shows_clears_it(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        foreach ([
            fn () => Category::factory()->for($shop)->create(),
            fn () => DiningTable::factory()->for($shop)->create(),
            fn () => FeatureGrant::factory()->for($shop)->create(),
            fn () => Dish::factory()->for($shop)->create()->delete(),
        ] as $change) {
            $this->freshCloudflare();

            $change();
            $this->respond();

            $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
        }
    }

    public function test_a_restaurant_saved_without_a_change_clears_nothing(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        $shop->save();
        $shop->update(['name' => $shop->name]);
        $this->respond();

        Http::assertNothingSent();
    }

    /** So the 404 a guest got at that link before it existed is not kept. */
    public function test_a_new_restaurant_clears_its_link(): void
    {
        Restaurant::factory()->create(['slug' => 'olive']);
        $this->respond();

        $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
    }

    public function test_a_new_link_clears_the_old_one_too(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        $shop->update(['slug' => 'olive-tree']);
        $this->respond();

        $this->assertEqualsCanonicalizing(['qayema.test/olive-tree', 'qayema.test/olive'], $this->purges()[0]['prefixes']);
    }

    public function test_a_deleted_restaurant_clears_the_link_it_had(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        $shop->delete();
        $this->respond();

        $this->assertContains(['prefixes' => ['qayema.test/olive']], $this->purges());
    }

    public function test_a_reorder_clears_the_menu_though_it_saves_no_model(): void
    {
        [$shop, $first, $second] = $this->quietly(function () {
            $shop = $this->owner(['slug' => 'olive']);

            return [$shop, Category::factory()->for($shop)->create(), Category::factory()->for($shop)->create()];
        });

        DisplayOrder::apply($shop->categories(), [$second->id, $first->id]);
        $this->respond();

        $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
    }

    public function test_a_new_dish_photo_clears_the_menu(): void
    {
        $dish = $this->quietly(fn () => Dish::factory()->for($this->owner(['slug' => 'olive']))->create());

        $dish->media()->create([
            'uuid' => fake()->uuid(), 'collection_name' => 'image', 'name' => 'x', 'file_name' => 'x.webp',
            'mime_type' => 'image/webp', 'disk' => 'public', 'conversions_disk' => 'public', 'size' => 1,
            'manipulations' => [], 'custom_properties' => [], 'generated_conversions' => [], 'responsive_images' => [],
        ]);
        $this->respond();

        $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
    }

    /** Every menu, by the menus' host: the zone's other hosts (the dashboard, the photos) keep their copies. */
    public function test_a_package_or_a_design_clears_every_menu(): void
    {
        [$package, $template] = $this->quietly(fn () => [Package::factory()->create(), Template::factory()->create()]);

        $package->update(['price_cents' => 1500]);
        $this->respond();
        $this->assertSame([['hosts' => ['qayema.test']]], $this->purges());

        $this->freshCloudflare();
        $template->update(['is_active' => false]);
        $this->respond();
        $this->assertSame([['hosts' => ['qayema.test']]], $this->purges());
    }

    public function test_a_package_or_a_design_saved_without_a_change_clears_nothing(): void
    {
        [$package, $template] = $this->quietly(fn () => [Package::factory()->create()->fresh(), Template::factory()->create()->fresh()]);

        $package->save();
        $template->update(['is_active' => $template->is_active]);
        $this->respond();

        Http::assertNothingSent();
    }

    public function test_clearing_everything_covers_a_menu_cleared_in_the_same_request(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        app(MenuCache::class)->forget($shop);
        app(MenuCache::class)->forgetAll();
        $this->respond();

        $this->assertSame([['hosts' => ['qayema.test']]], $this->purges());
    }

    public function test_the_next_request_clears_again(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        app(MenuCache::class)->forget($shop);
        $this->respond();
        app(MenuCache::class)->forget($shop);
        $this->respond();

        $this->assertCount(2, $this->purges());
    }

    public function test_a_change_through_the_dashboard_is_cleared_after_its_answer(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));
        $category = Category::factory()->for($shop)->create();
        $this->freshCloudflare();

        $this->actingAs($shop->user)
            ->patchJson(route('api.categories.update', $category), ['name' => ['en' => 'Grills']])
            ->assertOk();

        $this->assertSame([['prefixes' => ['qayema.test/olive']]], $this->purges());
    }

    public function test_many_menus_go_a_hundred_to_a_call(): void
    {
        $shops = $this->quietly(fn () => Restaurant::factory()->count(MenuCache::PREFIXES_A_CALL + 1)->create());

        foreach ($shops as $shop) {
            app(MenuCache::class)->forget($shop);
        }
        $this->respond();

        $this->assertCount(2, $this->purges());
        $this->assertCount(MenuCache::PREFIXES_A_CALL, $this->purges()[0]['prefixes']);
        $this->assertCount(1, $this->purges()[1]['prefixes']);
    }

    public function test_a_refused_clearing_waits_for_the_next_minute_and_is_said_at_once(): void
    {
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));
        $this->freshCloudflare(fn () => Http::response(['success' => false], 429));
        Log::spy();

        Dish::factory()->for($shop)->create();
        $this->respond();

        $this->assertSame(['prefixes|qayema.test/olive' => now()->getTimestamp()], PendingPurges::all());
        Log::shouldHaveReceived('warning')->with('Menu cache purge refused: trying again within the minute.', ['body' => ['prefixes' => ['qayema.test/olive']]])->once();
    }

    public function test_no_answer_at_all_waits_too(): void
    {
        $this->quietly(fn () => null);
        $this->freshCloudflare(fn () => throw new RuntimeException('No route to Cloudflare'));

        app(MenuCache::class)->forgetAll();
        $this->respond();

        $this->assertSame(['hosts|qayema.test'], array_keys(PendingPurges::all()));
    }

    /** The menus live at the main address, so a www. APP_URL still clears them there. */
    public function test_the_menus_are_cleared_at_the_main_address(): void
    {
        config(['app.url' => 'https://www.qayema.test']);
        $shop = $this->quietly(fn () => $this->owner(['slug' => 'olive']));

        app(MenuCache::class)->forget($shop);
        app(MenuCache::class)->forgetAll();
        $this->respond();

        $this->assertSame([['hosts' => ['qayema.test']]], $this->purges());
    }

    public function test_without_cloudflare_set_up_nothing_is_sent(): void
    {
        config(['services.cloudflare.api_token' => null]);

        Dish::factory()->for($this->owner())->create();
        $this->respond();

        Http::assertNothingSent();
        $this->assertSame([], PendingPurges::all());
    }
}
