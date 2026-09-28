<?php

namespace Tests\Feature\Packages;

use App\Enums\Feature;
use App\Enums\PackageStatus;
use App\Models\FeatureGrant;
use App\Models\Package;
use App\Services\Packages\PackageAssigner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * A package is in force from its start to its end (or forever), changes go
 * through PackageAssigner, and every change leaves a line in the history.
 */
class PackageDatesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function assigner(): PackageAssigner
    {
        return app(PackageAssigner::class);
    }

    public function test_a_package_that_has_not_started_gives_nothing_yet(): void
    {
        $restaurant = $this->owner();

        $this->assigner()->assign($restaurant, Package::findBySlug('premium'), now()->addWeek());

        $restaurant = $restaurant->fresh();
        $this->assertSame(PackageStatus::Scheduled, $restaurant->packageStatus());
        $this->assertSame('free', $restaurant->effectivePackage()->slug);
        $this->assertSame(40, $restaurant->dish_limit);

        $this->travel(8)->days();

        $this->assertSame(PackageStatus::Active, $restaurant->fresh()->packageStatus());
        $this->assertSame(500, $restaurant->fresh()->dish_limit);
    }

    public function test_the_cached_answer_ends_when_the_package_does(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now(), now()->addMinute());

        $this->assertSame(150, $restaurant->fresh()->dish_limit);

        // No write, no flush: only the clock moves past the end.
        $this->travel(2)->minutes();

        $this->assertSame(40, $restaurant->fresh()->dish_limit);
    }

    public function test_the_cached_answer_ends_when_a_grant_does(): void
    {
        $restaurant = $this->owner();
        FeatureGrant::factory()->for($restaurant)->forFeature(Feature::DishLimit, 10)->create(['ends_at' => now()->addMinute()]);

        $this->assertSame(50, $restaurant->fresh()->dish_limit);

        $this->travel(2)->minutes();

        $this->assertSame(40, $restaurant->fresh()->dish_limit);
    }

    public function test_assign_for_months_and_forever(): void
    {
        $restaurant = $this->owner();
        $pro = Package::findBySlug('pro');

        $this->assigner()->assign($restaurant, $pro, now(), PackageAssigner::endAfter(null, 3));
        $this->assertTrue($restaurant->fresh()->package_ends_at->isSameDay(now()->addMonthsNoOverflow(3)));

        $this->assigner()->assign($restaurant, $pro);
        $this->assertNull($restaurant->fresh()->package_ends_at, 'No end date is forever.');
    }

    public function test_extend_adds_to_the_current_end(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now(), now()->addDays(10));

        $this->assigner()->extend($restaurant->fresh(), 1);

        $this->assertTrue($restaurant->fresh()->package_ends_at->isSameDay(now()->addDays(10)->addMonthNoOverflow()));
    }

    public function test_extending_an_ended_package_starts_again_from_today(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now()->subMonths(2), now()->subDay());
        $this->assertTrue($restaurant->fresh()->packageExpired());

        $this->assigner()->extend($restaurant->fresh(), 1);

        $restaurant = $restaurant->fresh();
        $this->assertSame(PackageStatus::Active, $restaurant->packageStatus());
        $this->assertTrue($restaurant->package_ends_at->isSameDay(now()->addMonthNoOverflow()));
        $this->assertSame(150, $restaurant->dish_limit);
    }

    public function test_extend_forever_clears_the_end(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now(), now()->addDays(3));

        $this->assigner()->extend($restaurant->fresh(), null);

        $this->assertNull($restaurant->fresh()->package_ends_at);
    }

    public function test_reset_puts_it_back_on_the_default_forever(): void
    {
        $restaurant = $this->ownerOn('premium', ['package_ends_at' => now()->addYear()]);

        $this->assigner()->reset($restaurant, 'Stopped paying.');

        $restaurant = $restaurant->fresh();
        $this->assertSame('free', $restaurant->package->slug);
        $this->assertNull($restaurant->package_ends_at);
    }

    public function test_every_change_is_in_the_history_with_who_and_why(): void
    {
        $restaurant = $this->owner();
        $admin = $this->admin();
        $this->actingAs($admin);

        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now(), now()->addMonth(), 'Paid by bank transfer.');

        $history = $restaurant->packageChanges()->get();
        $this->assertCount(2, $history, 'The creation and the change.');

        $change = $history->first();
        $this->assertSame('free', $change->fromPackage->slug);
        $this->assertSame('pro', $change->toPackage->slug);
        $this->assertSame($admin->id, $change->changed_by);
        $this->assertSame('Paid by bank transfer.', $change->note);
        $this->assertNotNull($change->ends_at);

        $this->assertNull($history->last()->from_package_id, 'The first line is the restaurant being created.');
    }

    public function test_an_edit_that_does_not_touch_the_package_writes_no_history(): void
    {
        $restaurant = $this->owner();

        $restaurant->update(['phone' => '+96170000000']);

        $this->assertSame(1, $restaurant->packageChanges()->count());
    }

    public function test_the_session_reports_the_dates_of_the_package_in_force(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now()->subDay(), now()->addDays(10)->addHour());

        $this->actingAs($restaurant->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.package.slug', 'pro')
            ->assertJsonPath('data.restaurant.package.days_left', 11)
            ->assertJsonPath('data.restaurant.lapsed', null)
            ->assertJsonPath('data.restaurant.upcoming', null);
    }

    public function test_the_session_reports_a_package_that_ended(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now()->subMonth(), now()->subDay());

        $this->actingAs($restaurant->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.package.slug', 'free')
            ->assertJsonPath('data.restaurant.package.ends_at', null)
            ->assertJsonPath('data.restaurant.package.days_left', null)
            ->assertJsonPath('data.restaurant.lapsed.slug', 'pro')
            ->assertJsonPath('data.restaurant.lapsed.name.en', 'Pro');
    }

    public function test_the_session_reports_a_package_still_to_come(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('premium'), now()->addDays(3));

        $this->actingAs($restaurant->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.package.slug', 'free')
            ->assertJsonPath('data.restaurant.upcoming.slug', 'premium')
            ->assertJsonPath('data.restaurant.upcoming.ends_at', null);
    }
}
