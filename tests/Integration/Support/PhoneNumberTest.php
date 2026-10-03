<?php

namespace Tests\Integration\Support;

use App\Support\PhoneNumber;
use Tests\TestCase;

/**
 * Dial codes come from config/countries.php, so this needs the app. The
 * restaurant's own number is covered through WhatsAppLink; these are the
 * cases a guest typing into the cart brings.
 */
class PhoneNumberTest extends TestCase
{
    public function test_a_national_number_gets_its_country_code(): void
    {
        $this->assertSame('96170123456', PhoneNumber::international('LB', '70 123 456'));
        $this->assertSame('96170123456', PhoneNumber::international('LB', '070-123-456'));
        $this->assertSame('971501234567', PhoneNumber::international('AE', '050 123 4567'));
    }

    public function test_a_number_typed_with_its_code_is_not_doubled(): void
    {
        $this->assertSame('96170123456', PhoneNumber::international('LB', '+961 70 123 456'));
        $this->assertSame('96170123456', PhoneNumber::international('LB', '00961 70 123 456'));
        // No "+", but too long to be a Lebanese number on its own.
        $this->assertSame('96170123456', PhoneNumber::international('LB', '961 70 123 456'));
        $this->assertSame('919123456789', PhoneNumber::international('IN', '91 91234 56789'));
    }

    /** A national number may begin with the country's own code. */
    public function test_a_national_number_that_starts_like_its_code_keeps_it(): void
    {
        $this->assertSame('919123456789', PhoneNumber::international('IN', '91234 56789'));
        $this->assertSame('393931234567', PhoneNumber::international('IT', '393 123 4567'));
    }

    public function test_italy_keeps_its_leading_zero(): void
    {
        $this->assertSame('39061234567', PhoneNumber::international('IT', '06 1234567'));
    }

    /** What the guest's form gets back to change an order, and sends again. */
    public function test_a_stored_number_comes_back_the_same(): void
    {
        foreach (['+919123456789', '+96170123456', '+971501234567', '+39061234567'] as $stored) {
            $split = PhoneNumber::split($stored);

            $this->assertSame(ltrim($stored, '+'), PhoneNumber::international($split['country'], $split['national']), $stored);
        }
    }

    public function test_nothing_usable_is_null(): void
    {
        $this->assertNull(PhoneNumber::international('LB', ''));
        $this->assertNull(PhoneNumber::international('LB', null));
        $this->assertNull(PhoneNumber::international('LB', 'call me'));
    }

    public function test_longer_than_any_real_number_is_null(): void
    {
        $this->assertNull(PhoneNumber::international('LB', '1234567890123456'));
        $this->assertNull(PhoneNumber::international(null, '1234567890123456'));
    }

    public function test_without_a_country_only_an_international_number_is_trusted(): void
    {
        $this->assertSame('96170123456', PhoneNumber::international(null, '+961 70 123 456'));
        $this->assertNull(PhoneNumber::international(null, '70 123 456'));
        $this->assertNull(PhoneNumber::international('ZZ', '70 123 456'));
    }
}
