<?php

namespace Tests\Unit\Support;

use App\Models\Template;
use App\Support\Color;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ColorTest extends TestCase
{
    private const GOLD = [248, 211, 141];

    private const DARK_INK = '#111418';

    private const WHITE_INK = '#FFFFFF';

    /**
     * @return array<string, array{0: mixed, 1: bool}>
     */
    public static function hexProvider(): array
    {
        return [
            'lowercase six digits' => ['#abcdef', true],
            'uppercase six digits' => ['#ABCDEF', true],
            'mixed case with digits' => ['#aB12c9', true],
            'black' => ['#000000', true],
            'white' => ['#FFFFFF', true],
            'three digits' => ['#abc', false],
            'eight digits with alpha' => ['#abcdef12', false],
            'seven digits' => ['#abcdef1', false],
            'no hash' => ['abcdef', false],
            'two hashes' => ['##abcdef', false],
            'non-hex letter' => ['#abcdeg', false],
            'leading space' => [' #abcdef', false],
            'trailing space' => ['#abcdef ', false],
            'trailing newline' => ["#abcdef\n", false],
            'css name' => ['red', false],
            'rgb function' => ['rgb(0, 0, 0)', false],
            'empty string' => ['', false],
            'null' => [null, false],
            'integer' => [0xABCDEF, false],
            'array' => [['#abcdef'], false],
            'boolean' => [true, false],
        ];
    }

    #[DataProvider('hexProvider')]
    public function test_is_hex_accepts_only_six_digit_hex_with_a_hash(mixed $value, bool $expected): void
    {
        $this->assertSame($expected, Color::isHex($value));
    }

    public function test_the_validation_rule_is_the_same_pattern_is_hex_uses(): void
    {
        $this->assertSame('regex:/^#[0-9A-Fa-f]{6}$/D', Color::RULE);

        $pattern = substr(Color::RULE, strlen('regex:'));

        foreach (self::hexProvider() as [$value, $expected]) {
            if (! is_string($value)) {
                continue;
            }

            $this->assertSame($expected, preg_match($pattern, $value) === 1, "Rule and isHex disagree on [{$value}].");
        }
    }

    /**
     * @return array<string, array{0: string, 1: array{0: int, 1: int, 2: int}}>
     */
    public static function rgbProvider(): array
    {
        return [
            'six digits' => ['#1A2B3C', [26, 43, 60]],
            'six digits lowercase' => ['#1a2b3c', [26, 43, 60]],
            'black' => ['#000000', [0, 0, 0]],
            'white' => ['#FFFFFF', [255, 255, 255]],
            'three digits are doubled' => ['#abc', [170, 187, 204]],
            'three digits uppercase' => ['#F0A', [255, 0, 170]],
            'six digits without a hash' => ['1A2B3C', [26, 43, 60]],
            'three digits without a hash' => ['fff', [255, 255, 255]],
            'extra leading hashes are stripped' => ['##00ff00', [0, 255, 0]],
            'the default gold' => [Template::DEFAULT_PRIMARY_COLOR, self::GOLD],
        ];
    }

    /**
     * @param  array{0: int, 1: int, 2: int}  $expected
     */
    #[DataProvider('rgbProvider')]
    public function test_rgb_reads_three_and_six_digit_hex(string $hex, array $expected): void
    {
        $this->assertSame($expected, Color::rgb($hex));
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function garbageProvider(): array
    {
        return [
            'empty' => [''],
            'just a hash' => ['#'],
            'four digits' => ['#abcd'],
            'five digits' => ['#abcde'],
            'seven digits' => ['#abcdef1'],
            'eight digits' => ['#abcdef12'],
            'three non-hex characters' => ['#xyz'],
            'six non-hex characters' => ['#zzzzzz'],
            'one bad character' => ['#12345g'],
            'css name' => ['red'],
            'leading space' => [' #abcdef'],
            'rgb function' => ['rgb(1,2,3)'],
            'trailing newline' => ["#abcdef\n"],
        ];
    }

    #[DataProvider('garbageProvider')]
    public function test_rgb_reads_anything_else_as_qayema_gold(string $hex): void
    {
        $this->assertSame(self::GOLD, Color::rgb($hex));
    }

    public function test_rgb_returns_integers(): void
    {
        foreach (Color::rgb('#0a0B0c') as $channel) {
            $this->assertIsInt($channel);
        }

        $this->assertSame([10, 11, 12], Color::rgb('#0a0B0c'));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function inkProvider(): array
    {
        return [
            'white takes dark ink' => ['#FFFFFF', self::DARK_INK],
            'black takes white ink' => ['#000000', self::WHITE_INK],
            'the gold takes dark ink' => ['#F8D38D', self::DARK_INK],
            'pure red is strong' => ['#FF0000', self::WHITE_INK],
            'pure green is pale by luminance' => ['#00FF00', self::DARK_INK],
            'pure blue is strong' => ['#0000FF', self::WHITE_INK],
            'yellow is pale' => ['#FFFF00', self::DARK_INK],
            'cyan is pale' => ['#00FFFF', self::DARK_INK],
            'magenta is strong' => ['#FF00FF', self::WHITE_INK],
            'navy is strong' => ['#1E2A44', self::WHITE_INK],
            'three digit white' => ['#fff', self::DARK_INK],
            'three digit black' => ['#000', self::WHITE_INK],
            'grey just under the line' => ['#9E9E9E', self::WHITE_INK],
            'grey just over the line' => ['#9F9F9F', self::DARK_INK],
            'garbage is read as gold' => ['not a colour', self::DARK_INK],
        ];
    }

    #[DataProvider('inkProvider')]
    public function test_ink_on_a_colour_follows_its_luminance(string $hex, string $expected): void
    {
        $this->assertSame($expected, Color::inkOn($hex));
    }

    /**
     * The line is a relative luminance of 0.62: a grey of 158 is 0.6196 and
     * gets white, 159 is 0.6235 and gets near-black.
     */
    public function test_the_ink_threshold_sits_between_grey_158_and_159(): void
    {
        $this->assertSame(self::WHITE_INK, Color::inkOn(sprintf('#%1$02X%1$02X%1$02X', 158)));
        $this->assertSame(self::DARK_INK, Color::inkOn(sprintf('#%1$02X%1$02X%1$02X', 159)));
    }

    public function test_ink_is_one_of_exactly_two_colours(): void
    {
        foreach (['#123456', '#789ABC', '#DEF012', '#808080', '#C0C0C0'] as $hex) {
            $this->assertContains(Color::inkOn($hex), [self::DARK_INK, self::WHITE_INK]);
        }
    }
}
