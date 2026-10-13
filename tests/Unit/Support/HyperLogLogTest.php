<?php

namespace Tests\Unit\Support;

use App\Support\HyperLogLog;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * App\Support\HyperLogLog: distinct visitors counted without keeping them,
 * close to exact while few, within a few percent when many, and merged
 * across days without counting a returning guest twice.
 */
class HyperLogLogTest extends TestCase
{
    /** @return list<string> */
    private static function ids(int $count, string $prefix = 'visitor-'): array
    {
        return array_map(fn (int $i): string => $prefix.$i, range(1, $count));
    }

    public function test_nobody_counts_zero(): void
    {
        $this->assertSame(0, (new HyperLogLog)->count());
        $this->assertSame(0, HyperLogLog::of([])->count());
    }

    public function test_the_same_visitor_is_counted_once(): void
    {
        $this->assertSame(1, HyperLogLog::of(['a', 'a', 'a'])->count());
        $this->assertSame(3, HyperLogLog::of(['a', 'b', 'c', 'b', 'a'])->count());
    }

    /** @return array<string, array{0: int, 1: float}> */
    public static function sizes(): array
    {
        return [
            'a quiet day' => [25, 0.0],
            'a busy day' => [400, 0.03],
            'a month' => [6000, 0.04],
            'a year' => [60000, 0.05],
        ];
    }

    #[DataProvider('sizes')]
    public function test_it_stays_close_to_the_true_count(int $count, float $error): void
    {
        $estimate = HyperLogLog::of(self::ids($count))->count();

        $this->assertEqualsWithDelta($count, $estimate, max(1, $count * $error));
    }

    public function test_two_days_merge_into_their_visitors_together(): void
    {
        // 3,000 one day, 3,000 the next, 1,500 of them on both.
        $monday = HyperLogLog::of(self::ids(3000));
        $tuesday = HyperLogLog::of(array_map(fn (int $i): string => 'visitor-'.$i, range(1501, 4500)));

        $both = $monday->merge($tuesday)->count();

        $this->assertEqualsWithDelta(4500, $both, 4500 * 0.04);
    }

    public function test_it_survives_being_stored_and_stays_small(): void
    {
        $sketch = HyperLogLog::of(self::ids(5000));
        $stored = $sketch->toStored();

        $this->assertSame($sketch->count(), HyperLogLog::fromStored($stored)->count());
        $this->assertLessThan(2500, strlen($stored));
        $this->assertLessThan(100, strlen(HyperLogLog::of(['one'])->toStored()));
    }

    public function test_nothing_stored_or_something_unreadable_is_an_empty_sketch(): void
    {
        $this->assertSame(0, HyperLogLog::fromStored(null)->count());
        $this->assertSame(0, HyperLogLog::fromStored('')->count());
        $this->assertSame(0, HyperLogLog::fromStored('not a sketch')->count());
        $this->assertSame(0, HyperLogLog::fromStored(gzcompress('too short'))->count());
    }
}
