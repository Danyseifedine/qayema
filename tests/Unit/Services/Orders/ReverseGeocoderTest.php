<?php

namespace Tests\Unit\Services\Orders;

use App\Services\Orders\ReverseGeocoder;
use PHPUnit\Framework\TestCase;

/** What of OpenStreetMap's answer goes into the cart's address box. */
class ReverseGeocoderTest extends TestCase
{
    public function test_the_street_with_its_number_the_area_and_the_town(): void
    {
        $this->assertSame('12 Bliss Street, Ras Beirut, Beirut', ReverseGeocoder::line([
            'house_number' => '12', 'road' => 'Bliss Street', 'suburb' => 'Ras Beirut', 'city' => 'Beirut',
            'state' => 'Beirut Governorate', 'postcode' => '1107 2020', 'country' => 'Lebanon',
        ]));
    }

    public function test_the_closest_area_wins(): void
    {
        $this->assertSame('Hamra Street, Hamra, Beirut', ReverseGeocoder::line([
            'road' => 'Hamra Street', 'neighbourhood' => 'Hamra', 'suburb' => 'Ras Beirut', 'city' => 'Beirut',
        ]));
    }

    public function test_a_town_on_its_own_or_nothing_at_all(): void
    {
        $this->assertSame('Jounieh', ReverseGeocoder::line(['town' => 'Jounieh', 'country' => 'Lebanon']));
        $this->assertNull(ReverseGeocoder::line(['country' => 'Lebanon']));
    }

    public function test_a_name_given_twice_is_said_once(): void
    {
        $this->assertSame('Main Road, Byblos', ReverseGeocoder::line(['road' => 'Main Road', 'suburb' => 'Byblos', 'town' => 'Byblos']));
    }
}
