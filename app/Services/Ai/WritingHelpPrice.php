<?php

namespace App\Services\Ai;

use Illuminate\Support\Number;

/**
 * What the writing help's model charges, in US dollars per million tokens
 * in and out, and so what a request cost: Anthropic's list prices for the
 * Claude models it may run on, or AI_PRICE_INPUT and AI_PRICE_OUTPUT for
 * any other (a Gemini model, a newer Claude). Unknown, a request costs 0
 * and the admin is told to set them.
 */
final class WritingHelpPrice
{
    /** Dollars per million tokens, in and out (prompts under 100K tokens). */
    private const LIST = [
        'claude-haiku-5-5' => [0.10, 0.50],
        'claude-haiku-4-5' => [1.00, 5.00],
        'claude-sonnet-5-5' => [2.00, 10.00],
        'claude-sonnet-5' => [2.00, 10.00],
        'claude-opus-5-5' => [4.00, 20.00],
        'claude-opus-5' => [5.00, 25.00],
    ];

    /**
     * The model's price in and out per million tokens: the .env's when both
     * are set, else the list's, else null.
     *
     * @return array{0: float, 1: float}|null
     */
    public static function perMillion(): ?array
    {
        $input = config('services.writing_help.price_input');
        $output = config('services.writing_help.price_output');

        if (is_numeric($input) && is_numeric($output)) {
            return [(float) $input, (float) $output];
        }

        return self::LIST[(string) WritingHelpSetup::effectiveModel()] ?? null;
    }

    /** What $input and $output tokens cost now, in dollars, to the millionth. */
    public static function of(int $input, int $output): float
    {
        $price = self::perMillion();

        return $price === null ? 0.0 : round(($input * $price[0] + $output * $price[1]) / 1_000_000, 6);
    }

    /**
     * "$1.24", or under a cent its first two digits that are not 0 ("$0.0016",
     * "$0.000065"), so a small bill or a credit's worth still shows.
     */
    public static function format(float $dollars): string
    {
        if ($dollars <= 0 || $dollars >= 0.01) {
            return Number::currency($dollars, 'USD', precision: 2);
        }

        $precision = min(8, (int) ceil(-log10($dollars)) + 1);

        return '$'.rtrim(rtrim(number_format($dollars, $precision), '0'), '.');
    }

    /** "claude-haiku-5-5: $0.10 in, $0.50 out per million tokens", or what to set. */
    public static function describe(): string
    {
        if (WritingHelpSetup::provider() === null) {
            return 'Writing help is off (no AI_PROVIDER set); the figures are what it cost while it was on';
        }

        $price = self::perMillion();
        $model = WritingHelpSetup::effectiveModel() ?? 'the provider\'s cheapest model';

        return $price === null
            ? "No price known for {$model}: set AI_PRICE_INPUT and AI_PRICE_OUTPUT (dollars per million tokens)"
            : $model.': '.Number::currency($price[0], 'USD').' in, '.Number::currency($price[1], 'USD').' out per million tokens';
    }
}
