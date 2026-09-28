<?php

namespace Tests\Feature\Services\Packages;

use App\Enums\PackageStatus;
use App\Models\Package;
use App\Models\PackageChange;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * PackageAssigner at its edges: exact dates, what extending means in each
 * state, and what the history row carries.
 */
class PackageAssignerEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const NOW = '2026-09-28 12:00:00';

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(CarbonImmutable::parse(self::NOW, 'UTC'));
    }

    private function assigner(): PackageAssigner
    {
        return app(PackageAssigner::class);
    }

    private function latestChange(Restaurant $restaurant): PackageChange
    {
        return $restaurant->packageChanges()->first();
    }

    private function format(?\DateTimeInterface $moment): ?string
    {
        return $moment?->format('Y-m-d H:i:s');
    }

    public function test_the_durations_on_offer(): void
    {
        $this->assertSame([1, 3, 6, 12], PackageAssigner::DURATIONS);
    }

    public function test_assign_with_no_dates_starts_now_and_runs_forever(): void
    {
        $restaurant = $this->owner();

        $returned = $this->assigner()->assign($restaurant, Package::findBySlug('pro'));

        $this->assertSame($restaurant, $returned);
        $restaurant = $restaurant->fresh();
        $this->assertSame('pro', $restaurant->package->slug);
        $this->assertSame(self::NOW, $this->format($restaurant->package_started_at));
        $this->assertNull($restaurant->package_ends_at);
        $this->assertSame(PackageStatus::Active, $restaurant->packageStatus());
    }

    public function test_assign_from_a_future_date_is_scheduled(): void
    {
        $restaurant = $this->owner();

        $this->assigner()->assign($restaurant, Package::findBySlug('premium'), now()->addDays(3), now()->addDays(33), 'Starts after Ramadan.');

        $restaurant = $restaurant->fresh();
        $this->assertSame(PackageStatus::Scheduled, $restaurant->packageStatus());
        $this->assertSame('free', $restaurant->effectivePackage()->slug);
        $this->assertSame('2026-10-01 12:00:00', $this->format($restaurant->package_started_at));
        $this->assertSame('2026-10-31 12:00:00', $this->format($restaurant->package_ends_at));

        $change = $this->latestChange($restaurant);
        $this->assertSame('premium', $change->toPackage->slug);
        $this->assertSame('2026-10-01 12:00:00', $this->format($change->starts_at));
        $this->assertSame('2026-10-31 12:00:00', $this->format($change->ends_at));
        $this->assertSame('Starts after Ramadan.', $change->note);
    }

    public function test_extend_adds_months_to_a_future_end(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now()->subMonth(), CarbonImmutable::parse('2026-10-15 09:00:00', 'UTC'));

        $this->assigner()->extend($restaurant->fresh(), 3, 'Quarter paid.');

        $restaurant = $restaurant->fresh();
        $this->assertSame('2027-01-15 09:00:00', $this->format($restaurant->package_ends_at));
        $this->assertSame('2026-08-28 12:00:00', $this->format($restaurant->package_started_at), 'The start does not move.');
        $this->assertSame('Quarter paid.', $this->latestChange($restaurant)->note);
    }

    public function test_extend_never_overflows_into_the_next_month(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now(), CarbonImmutable::parse('2027-01-31 12:00:00', 'UTC'));

        $this->assigner()->extend($restaurant->fresh(), 1);

        $this->assertSame('2027-02-28 12:00:00', $this->format($restaurant->fresh()->package_ends_at));
    }

    public function test_extending_an_ended_package_restarts_it_today(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now()->subYear(), now()->subWeek());
        $this->assertSame(PackageStatus::Expired, $restaurant->fresh()->packageStatus());

        $this->assigner()->extend($restaurant->fresh(), 6);

        $restaurant = $restaurant->fresh();
        $this->assertSame(PackageStatus::Active, $restaurant->packageStatus());
        $this->assertSame(self::NOW, $this->format($restaurant->package_started_at));
        $this->assertSame('2027-03-28 12:00:00', $this->format($restaurant->package_ends_at));
        $this->assertSame('pro', $restaurant->effectivePackage()->slug);
    }

    public function test_extending_an_ended_package_forever_restarts_it_with_no_end(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now()->subYear(), now()->subDay());

        $this->assigner()->extend($restaurant->fresh(), null);

        $restaurant = $restaurant->fresh();
        $this->assertSame(self::NOW, $this->format($restaurant->package_started_at));
        $this->assertNull($restaurant->package_ends_at);
        $this->assertSame(PackageStatus::Active, $restaurant->packageStatus());
    }

    public function test_extending_a_scheduled_package_keeps_its_start(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), now()->addWeek(), now()->addWeek()->addMonth());

        $this->assigner()->extend($restaurant->fresh(), 1);

        $restaurant = $restaurant->fresh();
        $this->assertSame(PackageStatus::Scheduled, $restaurant->packageStatus());
        $this->assertSame('2026-10-05 12:00:00', $this->format($restaurant->package_started_at));
        $this->assertSame('2026-12-05 12:00:00', $this->format($restaurant->package_ends_at));
    }

    public function test_extending_a_forever_package_forever_changes_nothing_and_records_nothing(): void
    {
        $restaurant = $this->ownerOn('pro');
        $before = $restaurant->packageChanges()->count();

        $this->assigner()->extend($restaurant->fresh(), null, 'Nothing to do.');

        $restaurant = $restaurant->fresh();
        $this->assertNull($restaurant->package_ends_at);
        $this->assertSame($before, $restaurant->packageChanges()->count());
        $this->assertNull($restaurant->packageChangeNote);
    }

    public function test_reset_goes_back_to_the_default_from_now_forever(): void
    {
        $restaurant = $this->owner();
        $this->assigner()->assign($restaurant, Package::findBySlug('premium'), now()->addMonth(), now()->addYear());

        $this->actingAs($admin = $this->admin());
        $this->assigner()->reset($restaurant->fresh(), 'Cancelled the upgrade.');

        $restaurant = $restaurant->fresh();
        $this->assertSame('free', $restaurant->package->slug);
        $this->assertSame(self::NOW, $this->format($restaurant->package_started_at));
        $this->assertNull($restaurant->package_ends_at);

        $change = $this->latestChange($restaurant);
        $this->assertSame('premium', $change->fromPackage->slug);
        $this->assertSame('free', $change->toPackage->slug);
        $this->assertSame($admin->id, $change->changed_by);
        $this->assertSame('Cancelled the upgrade.', $change->note);
    }

    public function test_reset_with_no_default_package_keeps_the_one_assigned(): void
    {
        $restaurant = $this->ownerOn('pro', ['package_started_at' => now()->subMonth(), 'package_ends_at' => now()->addMonth()]);
        DB::table('packages')->update(['is_default' => false]);
        Cache::flush();

        $this->assigner()->reset($restaurant->fresh());

        $restaurant = $restaurant->fresh();
        $this->assertSame('pro', $restaurant->package->slug);
        $this->assertSame(self::NOW, $this->format($restaurant->package_started_at));
        $this->assertNull($restaurant->package_ends_at);
    }

    public function test_a_note_belongs_to_one_change_only(): void
    {
        $restaurant = $this->owner();

        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), note: 'First.');
        $this->assertNull($restaurant->packageChangeNote, 'Spent by the save.');

        $this->assigner()->assign($restaurant, Package::findBySlug('premium'));

        $this->assertSame([null, 'First.', null], $restaurant->packageChanges()->pluck('note')->all(), 'Newest first: premium, pro, then the creation.');
    }

    public function test_a_change_with_nobody_signed_in_has_no_author(): void
    {
        $restaurant = $this->owner();

        $this->assigner()->assign($restaurant, Package::findBySlug('pro'), note: 'From the console.');

        $change = $this->latestChange($restaurant);
        $this->assertNull($change->changed_by);
        $this->assertSame('From the console.', $change->note);
        $this->assertSame('free', $change->fromPackage->slug);
    }

    public function test_end_after_counts_from_a_future_end_or_else_from_now(): void
    {
        $future = CarbonImmutable::parse('2026-12-01 08:00:00', 'UTC');
        $past = CarbonImmutable::parse('2026-01-01 08:00:00', 'UTC');

        $this->assertSame('2027-03-01 08:00:00', $this->format(PackageAssigner::endAfter($future, 3)));
        $this->assertSame('2026-12-28 12:00:00', $this->format(PackageAssigner::endAfter($past, 3)));
        $this->assertSame('2027-09-28 12:00:00', $this->format(PackageAssigner::endAfter(null, 12)));
        $this->assertSame('2026-10-28 12:00:00', $this->format(PackageAssigner::endAfter(now(), 1)), 'Now is not the future.');
    }

    public function test_extending_a_forever_package_by_months_does_not_cut_it_short(): void
    {
        $restaurant = $this->ownerOn('pro');
        $before = $restaurant->packageChanges()->count();

        $this->assigner()->extend($restaurant->fresh(), 3, 'Would have shortened it.');

        $this->assertNull($restaurant->fresh()->package_ends_at);
        $this->assertSame($before, $restaurant->packageChanges()->count());
    }
}
