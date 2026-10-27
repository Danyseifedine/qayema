<?php

namespace Tests\Unit\Support;

use App\Support\Price;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class PriceTest extends TestCase
{
    /**
     * @return array<string, array{0: mixed, 1: string}>
     */
    public static function prices(): array
    {
        return [
            'whole, as stored' => ['12.00', '12'],
            'whole, with thousands' => ['10000.00', '10,000'],
            'half' => ['12.5', '12.50'],
            'cents' => ['7.25', '7.25'],
            'a float that is whole' => [24.0, '24'],
            'a float a hair off whole' => [9.999999, '10'],
            'zero' => ['0.00', '0'],
            'large, with cents' => ['1250000.75', '1,250,000.75'],
        ];
    }

    #[DataProvider('prices')]
    public function test_a_whole_price_shows_no_decimals_and_any_other_shows_two(mixed $amount, string $expected): void
    {
        $this->assertSame($expected, Price::format($amount));
    }

    /**
     * A line priced in the second currency counts in the main one at the
     * rate, to the cent, half up: the cart works it out the same way.
     */
    public function test_an_amount_turns_into_the_main_currency_at_the_rate(): void
    {
        // Main LBP, second USD: 1 USD = 89,000 LBP.
        $this->assertSame('445000.00', Price::toMain('5.00', '89000'));
        $this->assertSame('1112500.00', Price::toMain('12.50', '89000.000000000000'));
        // Main USD, second LBP: 450,000 LBP is about $5.06.
        $this->assertSame('5.06', Price::toMain('450000.00', '0.000011235955'));
        // Half a cent goes up.
        $this->assertSame('0.13', Price::toMain('0.25', '0.5'));
    }

    public function test_an_amount_off_in_the_main_currency_reads_in_the_second(): void
    {
        // L.L 89,000 off a dish priced in dollars is $1.
        $this->assertSame('1.00', Price::fromMain('89000', '89000'));
        $this->assertSame('0.17', Price::fromMain('15000', '89000'));
    }

    public function test_a_rate_reads_without_trailing_zeros(): void
    {
        $this->assertSame('89000', Price::rate('89000.000000000000'));
        $this->assertSame('0.000011235955', Price::rate('0.000011235955'));
        $this->assertSame('1.5', Price::rate('1.500000000000'));
        $this->assertSame('42', Price::rate('42'));
    }

    /** A sign stays on the number; a symbol in letters gets a small space. */
    public function test_a_symbol_in_letters_is_spaced_from_the_amount(): void
    {
        $this->assertSame('$5', Price::money('$', '5.00'));
        $this->assertSame('€12.50', Price::money('€', '12.5'));
        $this->assertSame('L.L 89,000', Price::money('L.L', '89000'));
        $this->assertSame('د.إ 20', Price::money('د.إ', '20'));
    }
}
