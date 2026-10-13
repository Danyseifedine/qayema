<?php

namespace App\Support;

/**
 * Counts distinct things (menu visitors) without keeping them: a HyperLogLog
 * sketch. Each id is hashed; 4,096 registers each keep the longest run of
 * leading zeros seen among the ids that fall in it, and from those the number
 * of distinct ids is estimated (typically about 1% off, rarely past 3%, and
 * close to exact while small). Two sketches merge into the sketch of both, so a day's visitors
 * stored apart still add up to the visitors of any range, a guest who came
 * on two days counted once.
 *
 * Stored compressed: a day with few visitors leaves most registers at zero.
 */
class HyperLogLog
{
    /** 2^12 registers: about 1.6% standard error, 4 KB before compression. */
    private const BITS = 12;

    private const REGISTERS = 1 << self::BITS;

    /**
     * Register (1 to REGISTERS, as unpack() numbers them) => longest run
     * seen, only those above zero.
     *
     * @var array<int, int>
     */
    private array $registers = [];

    public static function fromStored(?string $stored): self
    {
        $sketch = new self;

        if ($stored === null || $stored === '') {
            return $sketch;
        }

        // Something that is not a sketch reads as an empty one, quietly.
        set_error_handler(fn (): bool => true);
        try {
            $raw = gzuncompress($stored);
        } finally {
            restore_error_handler();
        }

        if ($raw === false || strlen($raw) !== self::REGISTERS) {
            return $sketch;
        }

        // Built in, not a loop: a year of days is read on one page.
        $sketch->registers = array_filter(unpack('C*', $raw));

        return $sketch;
    }

    /** @param  iterable<string>  $ids */
    public static function of(iterable $ids): self
    {
        $sketch = new self;

        foreach ($ids as $id) {
            $sketch->add((string) $id);
        }

        return $sketch;
    }

    public function add(string $id): void
    {
        // 64 bits of a fast, well-spread hash: the first 12 pick the
        // register, the rest give the run of zeros.
        $hash = unpack('J', hash('xxh64', $id, true))[1];
        $register = (($hash >> (64 - self::BITS)) & (self::REGISTERS - 1)) + 1;
        $rest = ($hash << self::BITS) | (1 << (self::BITS - 1));
        $rank = $this->leadingZeros($rest) + 1;

        if ($rank > ($this->registers[$register] ?? 0)) {
            $this->registers[$register] = $rank;
        }
    }

    public function merge(self $other): self
    {
        foreach ($other->registers as $register => $rank) {
            if ($rank > ($this->registers[$register] ?? 0)) {
                $this->registers[$register] = $rank;
            }
        }

        return $this;
    }

    public function count(): int
    {
        if ($this->registers === []) {
            return 0;
        }

        $sum = self::REGISTERS - count($this->registers);
        foreach ($this->registers as $rank) {
            $sum += 2 ** -$rank;
        }

        $alpha = 0.7213 / (1 + 1.079 / self::REGISTERS);
        $estimate = $alpha * self::REGISTERS * self::REGISTERS / $sum;
        $empty = self::REGISTERS - count($this->registers);

        // Few ids: counting the registers still empty is the closer estimate.
        if ($estimate <= 2.5 * self::REGISTERS && $empty > 0) {
            $estimate = self::REGISTERS * log(self::REGISTERS / $empty);
        }

        return (int) round($estimate);
    }

    public function toStored(): string
    {
        $raw = str_repeat("\0", self::REGISTERS);

        foreach ($this->registers as $register => $rank) {
            $raw[$register - 1] = chr($rank);
        }

        return gzcompress($raw, 9);
    }

    /** Never zero: add() sets a bit low down, so the run ends by then. */
    private function leadingZeros(int $value): int
    {
        $zeros = 0;
        while (($value & PHP_INT_MIN) === 0) {
            $value <<= 1;
            $zeros++;
        }

        return $zeros;
    }
}
