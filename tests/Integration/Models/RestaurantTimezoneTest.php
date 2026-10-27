<?php

namespace Tests\Integration\Models;

use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class RestaurantTimezoneTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_older_names_are_timezones_too(): void
    {
        $this->assertTrue(Restaurant::isTimezone('Asia/Beirut'));
        $this->assertTrue(Restaurant::isTimezone('Asia/Calcutta'));
        $this->assertFalse(Restaurant::isTimezone('Mars/Olympus'));
        $this->assertFalse(Restaurant::isTimezone(null));
    }

    public function test_the_likely_timezone_is_the_browsers_then_the_countrys(): void
    {
        $this->assertSame('Europe/Paris', Restaurant::likelyTimezone('Europe/Paris', 'LB'));
        $this->assertSame('Asia/Beirut', Restaurant::likelyTimezone('Mars/Olympus', 'lb'));
        $this->assertSame('Asia/Beirut', Restaurant::likelyTimezone(null, 'LB'));
        $this->assertNull(Restaurant::likelyTimezone(null, null));
        $this->assertNull(Restaurant::likelyTimezone(null, 'L1'));
        $this->assertNull(Restaurant::likelyTimezone(null, 'XX'));
    }

    public function test_restaurants_without_a_timezone_get_their_countrys(): void
    {
        $lebanese = $this->owner(['timezone' => null, 'country_code' => 'LB']);
        $set = $this->owner(['slug' => 'set', 'timezone' => 'Europe/Paris', 'country_code' => 'LB']);
        $nowhere = $this->owner(['slug' => 'nowhere', 'timezone' => null, 'country_code' => null]);

        $migration = require database_path('migrations/2026_10_19_100003_fill_missing_restaurant_timezones.php');
        $migration->up();

        $this->assertSame('Asia/Beirut', $lebanese->fresh()->timezone);
        $this->assertSame('Europe/Paris', $set->fresh()->timezone);
        $this->assertNull($nowhere->fresh()->timezone);
    }
}
