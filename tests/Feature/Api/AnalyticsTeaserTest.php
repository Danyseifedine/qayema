<?php

namespace Tests\Feature\Api;

use App\Models\MenuSession;
use App\Models\Package;
use App\Models\Restaurant;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * `GET /api/analytics/teaser`: this week's views, on every package. "This
 * week" is today and the six days before it, in the restaurant's timezone.
 */
class AnalyticsTeaserTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /**
     * 02:00 UTC on 15 June is 05:00 in Beirut (UTC+3), so Beirut's week
     * starts 9 June 00:00 local = 8 June 21:00 UTC, while UTC's starts at
     * 9 June 00:00 UTC.
     */
    private const NOW_UTC = '2026-06-15 02:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse(self::NOW_UTC, 'UTC'));
    }

    private function visit(Restaurant $restaurant, string $utc): void
    {
        MenuSession::factory()->for($restaurant)->create(['viewed_at' => CarbonImmutable::parse($utc, 'UTC')]);
    }

    /** Visits on both sides of the Beirut and UTC week boundaries. */
    private function seedAroundTheBoundary(Restaurant $restaurant): void
    {
        $this->visit($restaurant, '2026-06-08 20:59:59');
        $this->visit($restaurant, '2026-06-08 21:00:00');
        $this->visit($restaurant, '2026-06-08 23:30:00');
        $this->visit($restaurant, '2026-06-09 00:00:00');
        $this->visit($restaurant, '2026-06-12 12:00:00');
        $this->visit($restaurant, '2026-06-15 01:59:00');
    }

    public function test_a_guest_gets_a_401(): void
    {
        $this->getJson(route('api.analytics.teaser'))
            ->assertUnauthorized()
            ->assertJsonPath('code', 'unauthenticated');
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $this->actingAs($this->userWithoutRestaurant())
            ->getJson(route('api.analytics.teaser'))
            ->assertForbidden()
            ->assertJsonPath('code', 'forbidden');
    }

    public function test_the_free_package_gets_it(): void
    {
        $restaurant = $this->owner();
        $this->assertTrue($restaurant->effectivePackage()->is(Package::default()));
        $this->assertSame('free', $restaurant->effectivePackage()->slug);
        $this->visit($restaurant, '2026-06-14 10:00:00');

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertExactJson(['data' => ['range' => '7d', 'views' => 1]]);
    }

    public function test_the_week_starts_at_local_midnight_six_days_ago(): void
    {
        $restaurant = $this->owner(['timezone' => 'Asia/Beirut']);
        $this->seedAroundTheBoundary($restaurant);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertExactJson(['data' => ['range' => '7d', 'views' => 5]]);
    }

    /** The same rows seen from UTC: the three Beirut-only visits drop out. */
    public function test_a_restaurant_without_a_timezone_counts_in_utc(): void
    {
        $restaurant = $this->owner(['timezone' => null]);
        $this->seedAroundTheBoundary($restaurant);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertJsonPath('data.views', 3);
    }

    /** West of UTC the local day is still the 14th, so the week starts on the 8th. */
    public function test_a_timezone_behind_utc_reaches_further_back(): void
    {
        $restaurant = $this->owner(['timezone' => 'America/New_York']);
        $this->visit($restaurant, '2026-06-08 03:59:00');
        $this->visit($restaurant, '2026-06-08 04:00:00');
        $this->visit($restaurant, '2026-06-08 12:00:00');

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertJsonPath('data.views', 2);
    }

    public function test_an_unknown_timezone_falls_back_to_utc(): void
    {
        $restaurant = $this->owner(['timezone' => 'Mars/Olympus_Mons']);
        $this->seedAroundTheBoundary($restaurant);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertJsonPath('data.views', 3);
    }

    public function test_only_the_owners_own_visits_are_counted(): void
    {
        $restaurant = $this->owner();
        $this->visit($restaurant, '2026-06-14 10:00:00');
        $this->visit($this->owner(), '2026-06-14 10:00:00');
        $this->visit($this->owner(), '2026-06-14 11:00:00');

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertJsonPath('data.views', 1);
    }

    /** It is the QR page's number, so switching the Analytics page off leaves it. */
    public function test_it_ignores_the_analytics_switch(): void
    {
        $restaurant = $this->owner(['switched_off' => ['analytics']]);
        $this->visit($restaurant, '2026-06-14 10:00:00');

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertJsonPath('data.views', 1);
    }

    public function test_no_visits_is_zero(): void
    {
        $restaurant = $this->owner();

        $this->actingAs($restaurant->user)
            ->getJson(route('api.analytics.teaser'))
            ->assertOk()
            ->assertExactJson(['data' => ['range' => '7d', 'views' => 0]]);
    }
}
