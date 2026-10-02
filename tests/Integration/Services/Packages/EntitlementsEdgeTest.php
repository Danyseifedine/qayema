<?php

namespace Tests\Integration\Services\Packages;

use App\Enums\Feature;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Packages\Entitlements;
use App\Services\Packages\PackageAssigner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Entitlements at its edges: how long an answer is cached, that no date
 * passing ever leaves a stale one, flushing, and the arithmetic of grants.
 *
 * "Stale" is made on purpose by editing a package row with the query builder,
 * which skips the model hook that would flush every cached answer.
 */
class EntitlementsEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse('2026-09-28 12:00:00', 'UTC'));
    }

    /** Change a package's dish limit behind the cache's back. */
    private function quietlySetDishLimit(string $slug, ?int $limit): void
    {
        $features = json_decode((string) DB::table('packages')->where('slug', $slug)->value('features'), true);
        $features[Feature::DishLimit->value] = $limit;

        DB::table('packages')->where('slug', $slug)->update(['features' => json_encode($features)]);
    }

    private function dishLimit(Restaurant $restaurant): ?int
    {
        // Each check is its own request, as it is live: the answer memoized
        // for one request never carries into the next.
        app()->forgetScopedInstances();

        return Entitlements::for($restaurant->fresh())->limit(Feature::DishLimit);
    }

    public function test_an_answer_holds_for_the_configured_ttl_and_no_longer(): void
    {
        $restaurant = $this->owner();
        $this->assertSame(40, $this->dishLimit($restaurant));

        $this->quietlySetDishLimit('free', 99);

        $this->travel(299)->seconds();
        $this->assertSame(40, $this->dishLimit($restaurant), 'Still cached inside the five minutes.');

        $this->travel(2)->seconds();
        $this->assertSame(99, $this->dishLimit($restaurant));
    }

    public function test_the_ttl_comes_from_config(): void
    {
        config(['package.cache_ttl' => 60]);
        $restaurant = $this->owner();
        $this->assertSame(40, $this->dishLimit($restaurant));

        $this->quietlySetDishLimit('free', 99);

        $this->travel(59)->seconds();
        $this->assertSame(40, $this->dishLimit($restaurant));

        $this->travel(2)->seconds();
        $this->assertSame(99, $this->dishLimit($restaurant));
    }

    public function test_a_scheduled_package_takes_over_the_moment_it_starts(): void
    {
        $restaurant = $this->owner();
        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('premium'), now()->addSeconds(90));

        $this->assertSame(40, $this->dishLimit($restaurant), 'Not started: the default package.');

        $this->travel(89)->seconds();
        $this->assertSame(40, $this->dishLimit($restaurant));

        // No write and no flush: only the clock passes the start.
        $this->travel(2)->seconds();
        $this->assertSame(1000, $this->dishLimit($restaurant));
    }

    public function test_a_package_ends_the_moment_it_ends(): void
    {
        $restaurant = $this->owner();
        app(PackageAssigner::class)->assign($restaurant, Package::findBySlug('pro'), now()->subDay(), now()->addSeconds(45));

        $this->assertSame(150, $this->dishLimit($restaurant));

        $this->travel(44)->seconds();
        $this->assertSame(150, $this->dishLimit($restaurant));

        $this->travel(2)->seconds();
        $this->assertSame(40, $this->dishLimit($restaurant));
        $this->assertSame('pro', $restaurant->fresh()->package->slug, 'The lapsed assignment stays on the row.');
    }

    public function test_the_nearest_of_several_boundaries_wins(): void
    {
        $restaurant = $this->owner();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 10)->create(['ends_at' => now()->addMinutes(10)]);
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 5)->create(['ends_at' => now()->addMinutes(2)]);
        app(PackageAssigner::class)->assign($restaurant, Package::default(), now()->subDay(), now()->addHour());

        $this->assertSame(55, $this->dishLimit($restaurant));

        $this->travel(121)->seconds();
        $this->assertSame(50, $this->dishLimit($restaurant), 'The two-minute grant is gone.');

        $this->travel(8)->minutes();
        $this->assertSame(40, $this->dishLimit($restaurant), 'Then the ten-minute one.');
    }

    public function test_boundaries_already_passed_do_not_shorten_the_cache(): void
    {
        $restaurant = $this->owner(['package_started_at' => now()->subWeek()]);
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 10)->expired()->create();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::CategoryLimit, 2)->create(['ends_at' => now()->addYear()]);

        $this->assertSame(40, $this->dishLimit($restaurant));
        $this->quietlySetDishLimit('free', 99);

        // Were a past start or a lapsed grant counted, the answer would have
        // held for a second at most.
        $this->travel(30)->seconds();
        $this->assertSame(40, $this->dishLimit($restaurant));
    }

    public function test_flush_drops_one_restaurant_and_flush_all_drops_every_one(): void
    {
        $first = $this->owner();
        $second = $this->owner();
        $this->assertSame(40, $this->dishLimit($first));
        $this->assertSame(40, $this->dishLimit($second));

        $this->quietlySetDishLimit('free', 99);

        Entitlements::flush($first->id);
        $this->assertSame(99, $this->dishLimit($first));
        $this->assertSame(40, $this->dishLimit($second), 'Only the one flushed.');

        $this->quietlySetDishLimit('free', 77);
        Entitlements::flushAll();

        $this->assertSame(77, $this->dishLimit($first));
        $this->assertSame(77, $this->dishLimit($second));
        $this->assertTrue(Cache::has('entitlements:'.$first->id));
    }

    public function test_one_instance_keeps_its_answer_for_its_own_lifetime(): void
    {
        $restaurant = $this->owner();
        $entitlements = Entitlements::for($restaurant);
        $this->assertSame(40, $entitlements->limit(Feature::DishLimit));

        $this->quietlySetDishLimit('free', 99);
        Entitlements::flush($restaurant->id);

        $this->assertSame(40, $entitlements->limit(Feature::DishLimit), 'One request, one answer.');
        $this->assertSame(99, $this->dishLimit($restaurant));
    }

    public function test_a_cached_answer_from_before_a_new_feature_reads_it_as_its_default(): void
    {
        $restaurant = $this->owner();
        $cached = Entitlements::for($restaurant)->all();
        unset($cached[Feature::SocialLinkLimit->value], $cached[Feature::QrStudio->value]);
        Cache::put('entitlements:'.$restaurant->id, $cached, 300);

        $entitlements = Entitlements::for($restaurant->fresh());

        $this->assertSame(Feature::SocialLinkLimit->defaultValue(), $entitlements->limit(Feature::SocialLinkLimit));
        $this->assertFalse($entitlements->can(Feature::QrStudio));
    }

    public function test_every_feature_is_resolved(): void
    {
        $all = Entitlements::for($this->owner())->all();

        $this->assertSame(array_column(Feature::cases(), 'value'), array_keys($all));
    }

    public function test_unlimited_stays_unlimited_and_a_flag_is_on(): void
    {
        $restaurant = $this->ownerOn('custom');
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 10)->create();

        $entitlements = Entitlements::for($restaurant->fresh());

        $this->assertNull($entitlements->limit(Feature::DishLimit));
        $this->assertTrue($entitlements->can(Feature::DishLimit));
        $this->assertTrue($entitlements->can(Feature::Ordering));
        $this->assertFalse($restaurant->fresh()->hasReachedDishLimit());
    }

    public function test_limit_grants_add_up_and_flag_grants_only_switch_on(): void
    {
        $restaurant = $this->ownerOn('pro');
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 10)->create();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 5)->create(['ends_at' => now()->addMonth()]);
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 100)->expired()->create();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::QrStudio, 0)->create();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::Appearance, 1)->create();

        $entitlements = Entitlements::for($restaurant->fresh());

        $this->assertSame(165, $entitlements->limit(Feature::DishLimit));
        $this->assertFalse($entitlements->can(Feature::QrStudio), 'A grant of 0 unlocks nothing.');
        $this->assertSame(1, $entitlements->limit(Feature::Appearance), 'Pro already has it; a grant does not make it 2.');
    }

    public function test_a_scheduled_or_expired_package_falls_back_with_grants_still_on_top(): void
    {
        $scheduled = $this->ownerOn('premium', ['package_started_at' => now()->addDay()]);
        $expired = $this->ownerOn('premium', ['package_started_at' => now()->subMonth(), 'package_ends_at' => now()->subSecond()]);

        foreach ([$scheduled, $expired] as $restaurant) {
            FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 3)->create();
            FeatureGrant::factory()->for($restaurant)->forFeature(Feature::Ordering, 1)->create();

            $entitlements = Entitlements::for($restaurant->fresh());

            $this->assertSame(43, $entitlements->limit(Feature::DishLimit));
            $this->assertTrue($entitlements->can(Feature::Ordering));
            $this->assertFalse($entitlements->can(Feature::QrStudio), 'Premium is not in force.');
        }
    }

    public function test_with_no_package_at_all_every_feature_is_its_own_default(): void
    {
        $restaurant = $this->owner();
        DB::table('packages')->update(['is_default' => false]);
        DB::table('restaurants')->where('id', $restaurant->id)->update(['package_id' => null]);
        Cache::flush();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::CategoryLimit, 2)->create();

        $restaurant = $restaurant->fresh();
        $this->assertNull($restaurant->effectivePackage());

        $expected = [];
        foreach (Feature::cases() as $feature) {
            $expected[$feature->value] = $feature->defaultValue();
        }
        $expected[Feature::CategoryLimit->value] += 2;

        $this->assertSame($expected, Entitlements::for($restaurant)->all());
    }
}
