<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\WritingHelp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Never a dash in what the writing help puts on a menu
 * (WritingHelp::withoutDashes()): between words a comma, inside a word a
 * space, whatever the kind of dash.
 */
class WithoutDashesTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function dashes(): array
    {
        return [
            'an em dash between words' => ["Soups \u{2014} made daily", 'Soups, made daily'],
            'an em dash with no spaces' => ["Soups\u{2014}made daily", 'Soups, made daily'],
            'an en dash' => ["Lunch \u{2013} and dinner", 'Lunch, and dinner'],
            'a figure dash' => ["Open \u{2012} late", 'Open, late'],
            'a horizontal bar' => ["Warm \u{2015} welcoming", 'Warm, welcoming'],
            'a minus sign' => ["Hot \u{2212} cold", 'Hot, cold'],
            'a hyphen used as a dash' => ['Soups - and more', 'Soups, and more'],
            'a hyphen inside a word' => ['Hand-stretched dough', 'Hand stretched dough'],
            'several hyphens in a word' => ['A one-of-a-kind table', 'A one of a kind table'],
            'a dash that opens the text' => ["\u{2014} Fresh plates", 'Fresh plates'],
            'a dash that ends the text with a space before it' => ["Fresh plates \u{2014}", 'Fresh plates'],
            'two dashes in a row' => ["Soups \u{2014} \u{2014} made daily", 'Soups, made daily'],
            'a double hyphen used as a dash' => ['Soups -- made daily', 'Soups, made daily'],
            'a double hyphen inside a word' => ['Slow--cooked', 'Slow cooked'],
            'a dash that ends the text' => ['Fresh plates -', 'Fresh plates'],
            'dashes of every kind at once' => ["Wood-fired \u{2014} and slow \u{2013} cooked", 'Wood fired, and slow, cooked'],
            'arabic text keeps its words' => ["أطباق دافئة \u{2014} للمشاركة", 'أطباق دافئة, للمشاركة'],
        ];
    }

    #[DataProvider('dashes')]
    public function test_a_dash_never_reaches_the_menu(string $written, string $printed): void
    {
        $this->assertSame($printed, WritingHelp::withoutDashes($written));
    }

    public function test_text_without_a_dash_is_left_as_it_is(): void
    {
        $text = 'Warm bowls of soup to start your meal, or share across the table.';

        $this->assertSame($text, WritingHelp::withoutDashes($text));
        $this->assertSame('شوربة العدس، كل يوم.', WritingHelp::withoutDashes('شوربة العدس، كل يوم.'));
    }

    public function test_the_result_is_trimmed(): void
    {
        $this->assertSame('Soups', WritingHelp::withoutDashes('  Soups  '));
    }
}
