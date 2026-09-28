<?php

namespace Tests\Unit\Services\Media;

use App\Services\Media\UploadLimits;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class UploadLimitsTest extends TestCase
{
    /**
     * @return array<string, array{0: string, 1: int}>
     */
    public static function shorthandProvider(): array
    {
        return [
            'megabytes' => ['8M', 8 * 1024 * 1024],
            'lowercase megabytes' => ['2m', 2 * 1024 * 1024],
            'kilobytes' => ['512K', 512 * 1024],
            'gigabytes' => ['1G', 1024 * 1024 * 1024],
            'plain bytes' => ['1048576', 1048576],
            'padded' => ['  16M  ', 16 * 1024 * 1024],
            'empty means unset' => ['', 0],
            'unlimited' => ['-1', -1],
        ];
    }

    #[DataProvider('shorthandProvider')]
    public function test_it_reads_php_size_shorthand(string $value, int $expected): void
    {
        $this->assertSame($expected, UploadLimits::toBytes($value));
    }

    public function test_the_effective_limit_never_exceeds_what_the_app_asks_for(): void
    {
        $this->assertLessThanOrEqual(UploadLimits::APP_MAX_BYTES, UploadLimits::effectiveBytes());
        $this->assertSame(
            min(UploadLimits::APP_MAX_BYTES, UploadLimits::serverBytes()),
            UploadLimits::effectiveBytes(),
        );
    }

    public function test_it_describes_the_limit_in_whole_megabytes(): void
    {
        $this->assertMatchesRegularExpression('/^\d+(\.\d)? MB$/', UploadLimits::describe());
    }

    public function test_a_server_below_the_app_limit_is_reported_as_such(): void
    {
        $this->assertSame(
            UploadLimits::serverBytes() < UploadLimits::APP_MAX_BYTES,
            UploadLimits::serverIsBelowApp(),
        );
    }
}
