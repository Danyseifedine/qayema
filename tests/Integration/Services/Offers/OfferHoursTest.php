<?php

namespace Tests\Integration\Services\Offers;

use App\Enums\OfferRepeat;
use App\Models\Offer;
use App\Services\Offers\OfferSchedule;
use Carbon\CarbonImmutable;
use Tests\TestCase;

/**
 * When an offer runs, as a guest who taps it outside that time is told,
 * and whether it is on the menu then (shown outside its hours or not).
 */
class OfferHoursTest extends TestCase
{
    /** @param  array<int, array{from: string, to: string}>|null  $windows */
    private function schedule(OfferRepeat $repeat = OfferRepeat::Always, array $weekdays = [], array $monthDays = [], ?array $windows = null): OfferSchedule
    {
        return new OfferSchedule(timezone: 'Asia/Beirut', repeat: $repeat, weekdays: $weekdays, monthDays: $monthDays, windows: $windows);
    }

    public function test_it_names_the_days_then_the_hours_each_list_ending_in_its_and(): void
    {
        $wednesday = $this->schedule(OfferRepeat::Weekly, ['fri', 'wed'], windows: [['from' => '17:00', 'to' => '19:00']]);

        $this->assertSame('Available Wednesday and Friday, 5:00 PM to 7:00 PM.', $wednesday->describe('en'));
        $this->assertSame('Disponible mercredi et vendredi, de 17:00 à 19:00.', $wednesday->describe('fr'));
        $this->assertSame('متاح الأربعاء والجمعة، من 17:00 إلى 19:00.', $wednesday->describe('ar'));
    }

    public function test_days_of_the_month_and_every_day_with_hours(): void
    {
        $this->assertSame('Available days 1, 15 and 31 of the month.', $this->schedule(OfferRepeat::Monthly, monthDays: [15, 31, 1])->describe('en'));
        $this->assertSame(
            'Available every day, 12:00 PM to 2:00 PM and 10:00 PM to 2:00 AM.',
            $this->schedule(windows: [['from' => '22:00', 'to' => '02:00'], ['from' => '12:00', 'to' => '14:00']])->describe('en'),
        );
    }

    public function test_nothing_to_say_when_it_runs_all_day_every_day(): void
    {
        $this->assertNull($this->schedule()->describe('en'));
        $this->assertFalse($this->schedule()->hasHours());
    }

    public function test_shown_outside_its_hours_it_stays_on_the_menu_inside_its_dates(): void
    {
        // A Tuesday noon in Beirut; the deal runs on Wednesdays.
        $tuesday = CarbonImmutable::parse('2026-11-10 12:00', 'Asia/Beirut');
        $offer = new Offer(['repeat' => OfferRepeat::Weekly, 'weekdays' => ['wed'], 'is_active' => true, 'shows_outside_hours' => true]);

        $this->assertFalse($offer->isLiveAt($tuesday, 'Asia/Beirut'));
        $this->assertTrue($offer->isShownAt($tuesday, 'Asia/Beirut'));

        $offer->shows_outside_hours = false;
        $this->assertFalse($offer->isShownAt($tuesday, 'Asia/Beirut'));

        $offer->fill(['shows_outside_hours' => true, 'ends_at' => $tuesday->utc()->subHour()]);
        $this->assertFalse($offer->isShownAt($tuesday, 'Asia/Beirut'));

        $offer->fill(['ends_at' => null, 'is_active' => false]);
        $this->assertFalse($offer->isShownAt($tuesday, 'Asia/Beirut'));
    }
}
