<?php

namespace Tests\Integration\Admin;

use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Package;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use ErrorException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * PackageFields' static helpers: endsAt() turns the "for how long" fields
 * into an end date, stateFor() opens the form on what is there. Both read
 * `now()` when no start is given, so the clock is frozen.
 */
class PackageFieldsTest extends TestCase
{
    use CreatesOwners;
    use RefreshDatabase;

    private const NOW = '2026-03-10 14:30:00';

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow(Carbon::parse(self::NOW));
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_no_input_at_all_is_forever(): void
    {
        $this->assertNull(PackageFields::endsAt([]));
    }

    public function test_forever_has_no_end(): void
    {
        $this->assertNull(PackageFields::endsAt([
            'package_started_at' => '2026-01-01 00:00:00',
            'duration' => PackageFields::FOREVER,
            'months' => 6,
            'package_ends_at' => '2026-12-31 00:00:00',
        ]));
    }

    public function test_an_unknown_duration_is_read_as_forever(): void
    {
        $this->assertNull(PackageFields::endsAt(['duration' => 'weekly', 'package_ends_at' => '2026-12-31']));
        $this->assertNull(PackageFields::endsAt(['duration' => null]));
    }

    public function test_months_count_from_the_start(): void
    {
        $end = PackageFields::endsAt([
            'package_started_at' => '2026-01-15 10:00:00',
            'duration' => PackageFields::MONTHS,
            'months' => 3,
        ]);

        $this->assertInstanceOf(CarbonImmutable::class, $end);
        $this->assertSame('2026-04-15 10:00:00', $end->toDateTimeString());
    }

    public function test_each_offered_duration(): void
    {
        foreach ([1 => '2026-02-01', 3 => '2026-04-01', 6 => '2026-07-01', 12 => '2027-01-01'] as $months => $expected) {
            $end = PackageFields::endsAt([
                'package_started_at' => '2026-01-01',
                'duration' => PackageFields::MONTHS,
                'months' => $months,
            ]);

            $this->assertSame($expected, $end?->toDateString(), "{$months} months");
        }
    }

    public function test_a_month_from_the_31st_ends_on_the_last_day_of_a_shorter_month(): void
    {
        $end = PackageFields::endsAt([
            'package_started_at' => '2026-01-31 09:00:00',
            'duration' => PackageFields::MONTHS,
            'months' => 1,
        ]);

        $this->assertSame('2026-02-28 09:00:00', $end?->toDateTimeString(), 'Not overflowed into March.');
    }

    public function test_months_default_to_one(): void
    {
        $end = PackageFields::endsAt([
            'package_started_at' => '2026-05-20 08:00:00',
            'duration' => PackageFields::MONTHS,
        ]);

        $this->assertSame('2026-06-20 08:00:00', $end?->toDateTimeString());
    }

    public function test_months_sent_as_a_string_from_the_form(): void
    {
        $end = PackageFields::endsAt([
            'package_started_at' => '2026-01-01',
            'duration' => PackageFields::MONTHS,
            'months' => '6',
        ]);

        $this->assertSame('2026-07-01', $end?->toDateString());
    }

    /** A non-numeric month count casts to zero, so the package ends as it starts. */
    public function test_months_that_are_not_a_number_end_at_the_start(): void
    {
        $end = PackageFields::endsAt([
            'package_started_at' => '2026-01-01 12:00:00',
            'duration' => PackageFields::MONTHS,
            'months' => 'abc',
        ]);

        $this->assertSame('2026-01-01 12:00:00', $end?->toDateTimeString());
    }

    public function test_months_without_a_start_count_from_now(): void
    {
        $end = PackageFields::endsAt(['duration' => PackageFields::MONTHS, 'months' => 3]);

        $this->assertSame('2026-06-10 14:30:00', $end?->toDateTimeString());
    }

    public function test_a_null_start_counts_from_now(): void
    {
        $end = PackageFields::endsAt(['package_started_at' => null, 'duration' => PackageFields::MONTHS, 'months' => 1]);

        $this->assertSame('2026-04-10 14:30:00', $end?->toDateTimeString());
    }

    public function test_a_start_given_as_a_date_object(): void
    {
        $end = PackageFields::endsAt([
            'package_started_at' => Carbon::parse('2026-02-01 00:00:00'),
            'duration' => PackageFields::MONTHS,
            'months' => 12,
        ]);

        $this->assertSame('2027-02-01 00:00:00', $end?->toDateTimeString());
    }

    public function test_until_ends_on_the_date_given_whatever_the_start(): void
    {
        $end = PackageFields::endsAt([
            'package_started_at' => '2026-01-01',
            'duration' => PackageFields::UNTIL,
            'months' => 12,
            'package_ends_at' => '2026-09-30 23:59:00',
        ]);

        $this->assertInstanceOf(CarbonImmutable::class, $end);
        $this->assertSame('2026-09-30 23:59:00', $end->toDateTimeString());
    }

    public function test_until_takes_a_date_object(): void
    {
        $end = PackageFields::endsAt([
            'duration' => PackageFields::UNTIL,
            'package_ends_at' => Carbon::parse('2027-01-01 00:00:00'),
        ]);

        $this->assertSame('2027-01-01 00:00:00', $end?->toDateTimeString());
    }

    /** The form requires the date; a cleared picker (null) is parsed as now. */
    public function test_until_with_a_cleared_date_ends_now(): void
    {
        $end = PackageFields::endsAt(['duration' => PackageFields::UNTIL, 'package_ends_at' => null]);

        $this->assertSame(self::NOW, $end?->toDateTimeString());
    }

    /**
     * Unreachable from the form, which shows and requires the date whenever
     * "Until a date" is picked; called without the key at all it fails loudly
     * rather than inventing an end.
     */
    public function test_until_without_the_date_key_fails_loudly(): void
    {
        $this->expectException(ErrorException::class);
        $this->expectExceptionMessage('Undefined array key "package_ends_at"');

        PackageFields::endsAt(['duration' => PackageFields::UNTIL]);
    }

    public function test_until_with_a_date_that_is_not_one_throws(): void
    {
        $this->expectException(InvalidFormatException::class);

        PackageFields::endsAt(['duration' => PackageFields::UNTIL, 'package_ends_at' => 'someday soon']);
    }

    public function test_a_start_that_is_not_a_date_throws(): void
    {
        $this->expectException(InvalidFormatException::class);

        PackageFields::endsAt(['package_started_at' => 'whenever', 'duration' => PackageFields::FOREVER]);
    }

    public function test_the_month_choices_are_the_offered_durations(): void
    {
        $this->assertSame(
            [1 => '1 month', 3 => '3 months', 6 => '6 months', 12 => '12 months'],
            PackageFields::monthOptions(),
        );
    }

    public function test_the_package_choices_are_the_four_packages_in_order(): void
    {
        app()->setLocale('en');

        $this->assertSame(
            ['Free', 'Pro', 'Premium', 'Custom'],
            array_values(PackageFields::packageOptions()),
        );
        $this->assertSame(Package::findBySlug('pro')?->id, array_search('Pro', PackageFields::packageOptions(), true));
    }

    public function test_the_state_for_a_restaurant_on_a_forever_package(): void
    {
        $restaurant = $this->owner();

        $state = PackageFields::stateFor($restaurant);

        $this->assertSame($restaurant->package_id, $state['package_id']);
        $this->assertSame(self::NOW, Carbon::parse($state['package_started_at'])->toDateTimeString(), 'No start yet: now.');
        $this->assertSame(PackageFields::FOREVER, $state['duration']);
        $this->assertSame(1, $state['months']);
        $this->assertNull($state['package_ends_at']);
        $this->assertNull($state['note']);
    }

    public function test_the_state_for_a_restaurant_with_an_end_date_opens_on_until(): void
    {
        $restaurant = $this->ownerOn('pro', [
            'package_started_at' => '2026-01-01 00:00:00',
            'package_ends_at' => '2026-07-01 00:00:00',
        ]);

        $state = PackageFields::stateFor($restaurant);

        $this->assertSame(PackageFields::UNTIL, $state['duration']);
        $this->assertSame('2026-01-01 00:00:00', Carbon::parse($state['package_started_at'])->toDateTimeString());
        $this->assertSame('2026-07-01 00:00:00', Carbon::parse($state['package_ends_at'])->toDateTimeString());
        $this->assertSame(
            '2026-07-01 00:00:00',
            PackageFields::endsAt($state)?->toDateTimeString(),
            'Saving the form unchanged keeps the end.',
        );
    }
}
