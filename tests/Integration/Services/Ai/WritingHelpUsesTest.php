<?php

namespace Tests\Integration\Services\Ai;

use App\Enums\Feature;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Services\Ai\WritingHelpUses;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Services\Ai\WritingHelpUses: a restaurant's uses of the writing help
 * a day, as its package (and grants) give them.
 */
class WritingHelpUsesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.writing_help.provider' => 'anthropic',
            'ai.providers.anthropic.key' => 'test-key',
        ]);
    }

    private function uses(): WritingHelpUses
    {
        return app(WritingHelpUses::class);
    }

    public function test_each_use_taken_is_one_less_left(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 3);
        $owner = $this->owner();

        $this->assertSame(3, $this->uses()->limit($owner));
        $this->assertSame(3, $this->uses()->left($owner));

        $this->uses()->take($owner);
        $this->uses()->take($owner);

        $this->assertSame(1, $this->uses()->left($owner));
    }

    public function test_left_never_goes_below_none(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        $owner = $this->owner();

        $this->uses()->take($owner);
        $this->uses()->take($owner);

        $this->assertSame(0, $this->uses()->left($owner));
    }

    public function test_a_use_given_back_is_left_again_but_never_more_than_the_day_gives(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 2);
        $owner = $this->owner();

        $this->uses()->take($owner);
        $this->uses()->giveBack($owner);
        $this->assertSame(2, $this->uses()->left($owner));

        // Nothing taken, nothing to give back: still the day's two.
        $this->uses()->giveBack($owner);
        $this->assertSame(2, $this->uses()->left($owner));
        $this->uses()->take($owner);
        $this->assertSame(1, $this->uses()->left($owner));
    }

    public function test_with_no_daily_cap_nothing_is_counted(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, null);
        $owner = $this->owner();

        $this->uses()->take($owner);
        $this->uses()->giveBack($owner);

        $this->assertNull($this->uses()->limit($owner));
        $this->assertNull($this->uses()->left($owner));
    }

    public function test_the_uses_come_back_at_the_restaurants_own_midnight(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 2);
        $owner = $this->owner(['timezone' => 'Asia/Tokyo']);
        $this->travelTo(Carbon::parse('2026-10-11 22:00', 'Asia/Tokyo'));

        $this->uses()->take($owner);
        $this->uses()->take($owner);

        $this->assertSame(0, $this->uses()->left($owner));
        // Two hours to midnight in Tokyo, whatever the server's clock says.
        $this->assertSame(2 * 3600, $this->uses()->backIn($owner));

        $this->travel(2)->hours();
        $this->assertSame(2, $this->uses()->left($owner));
        $this->assertSame(24 * 3600, $this->uses()->backIn($owner));
    }

    public function test_a_late_first_use_does_not_hold_the_next_day_back(): void
    {
        // The day is the restaurant's, not 24 hours from the first use: used
        // up at 23:59, everything is back a minute later.
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        $owner = $this->owner(['timezone' => 'Asia/Beirut']);
        $this->travelTo(Carbon::parse('2026-10-11 23:59', 'Asia/Beirut'));
        $this->uses()->take($owner);
        $this->assertSame(0, $this->uses()->left($owner));

        $this->travel(1)->minutes();

        $this->assertSame(1, $this->uses()->left($owner));
    }

    public function test_the_same_moment_is_a_different_day_in_another_timezone(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        // 23:30 in Beirut is already the next day in Tokyo.
        $this->travelTo(Carbon::parse('2026-10-11 23:30', 'Asia/Beirut'));
        $beirut = $this->owner(['timezone' => 'Asia/Beirut']);
        $tokyo = $this->owner(['timezone' => 'Asia/Tokyo']);

        $this->assertSame(30 * 60, $this->uses()->backIn($beirut));
        $this->assertGreaterThan(30 * 60, $this->uses()->backIn($tokyo));
    }

    public function test_a_restaurant_with_no_timezone_counts_its_day_in_the_apps(): void
    {
        config(['app.timezone' => 'UTC']);
        $owner = $this->owner(['timezone' => null]);
        $this->travelTo(Carbon::parse('2026-10-11 23:00', 'UTC'));

        $this->assertSame(3600, $this->uses()->backIn($owner));
    }

    public function test_each_restaurant_counts_its_own(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 2);
        $owner = $this->owner();
        $other = $this->owner();

        $this->uses()->take($owner);
        $this->uses()->take($owner);

        $this->assertSame(0, $this->uses()->left($owner));
        $this->assertSame(2, $this->uses()->left($other));
    }

    public function test_a_package_lowered_mid_day_leaves_none_once_its_number_is_used(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 10);
        $owner = $this->owner();
        foreach (range(1, 5) as $use) {
            $this->uses()->take($owner);
        }

        $this->defaultPackageSets(Feature::WritingHelpLimit, 3);

        $this->assertSame(3, $this->uses()->limit($owner->fresh()));
        $this->assertSame(0, $this->uses()->left($owner->fresh()));
    }

    public function test_a_package_raised_mid_day_gives_the_difference_at_once(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 2);
        $owner = $this->owner();
        $this->uses()->take($owner);
        $this->uses()->take($owner);

        $this->defaultPackageSets(Feature::WritingHelpLimit, 5);

        $this->assertSame(3, $this->uses()->left($owner->fresh()));
    }

    public function test_a_grant_adds_to_the_package(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 5);
        $owner = $this->owner();
        FeatureGrant::factory()->for($owner)->forFeature(Feature::WritingHelpLimit, 20)->create();

        $this->assertSame(25, $this->uses()->limit($owner->fresh()));
    }

    public function test_a_package_that_ended_keeps_its_uses(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 5);
        $owner = $this->ownerOn('premium');
        $owner->forceFill(['package_started_at' => now()->subMonths(2), 'package_ends_at' => now()->subDay()])->save();

        $this->assertSame(200, $this->uses()->limit($owner->fresh()));
    }

    public function test_a_package_saved_before_the_limit_existed_reads_thirty(): void
    {
        $package = Package::default();
        $features = (array) $package->features;
        unset($features['writing_help_limit']);
        $package->update(['features' => $features]);

        $this->assertSame(30, $this->uses()->limit($this->owner()));
    }

    public function test_the_dashboard_hears_the_uses_or_null_when_off(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 4);
        $owner = $this->owner();
        $this->uses()->take($owner);

        $this->assertSame(['limit' => 4, 'left' => 3], $this->uses()->forDashboard($owner));

        config(['services.writing_help.provider' => null]);
        $this->assertNull($this->uses()->forDashboard($owner));

        config(['services.writing_help.provider' => 'anthropic']);
        $this->defaultPackageSets(Feature::WritingHelpLimit, 0);
        $this->assertNull($this->uses()->forDashboard($owner->fresh()));
    }
}
