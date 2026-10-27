<?php

namespace Tests\Integration\Services\Menu;

use App\Models\Restaurant;
use App\Services\Menu\OpeningHours;
use Tests\TestCase;

/** The timezone the hours are judged in, where the app's settings decide it; the rules are Unit/Services/Menu/OpeningHoursTest. */
class OpeningHoursTest extends TestCase
{
    private function timezoneOf(?string $stored): string
    {
        return OpeningHours::for(new Restaurant(['timezone' => $stored]))->timezone();
    }

    public function test_a_timezone_that_is_not_a_real_one_falls_back_to_the_apps(): void
    {
        config(['app.timezone' => 'Asia/Beirut']);

        $this->assertSame('Asia/Beirut', $this->timezoneOf('Mars/Olympus'));
        $this->assertSame('Asia/Beirut', $this->timezoneOf(null));
        $this->assertSame('Asia/Beirut', $this->timezoneOf(''));
        $this->assertSame('Asia/Calcutta', $this->timezoneOf('Asia/Calcutta'));
    }

    public function test_without_a_real_app_timezone_it_is_utc(): void
    {
        config(['app.timezone' => 'Nowhere/Land']);

        $this->assertSame('UTC', $this->timezoneOf('Mars/Olympus'));
    }
}
