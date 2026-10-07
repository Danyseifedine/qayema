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
}
