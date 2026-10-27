<?php

namespace Tests\Unit\Services\Ai;

use App\Services\Ai\WritingHelpPrice;
use Tests\TestCase;

/**
 * What the writing help's model charges per million tokens, and so what a
 * request cost: the Claude list prices, or the .env's for any other model.
 */
class WritingHelpPriceTest extends TestCase
{
    public function test_a_claude_model_is_priced_from_the_list(): void
    {
        config(['services.writing_help.provider' => 'anthropic', 'services.writing_help.model' => 'claude-haiku-5-5']);

        $this->assertSame([0.10, 0.50], WritingHelpPrice::perMillion());
        // 3,000 in and 1,000 out: $0.0003 + $0.0005.
        $this->assertSame(0.0008, WritingHelpPrice::of(3000, 1000));
        $this->assertSame('claude-haiku-5-5: $0.10 in, $0.50 out per million tokens', WritingHelpPrice::describe());

        config(['services.writing_help.model' => 'claude-opus-5-5']);
        $this->assertSame(0.024, WritingHelpPrice::of(1000, 1000));
    }

    public function test_the_env_prices_any_other_model_and_wins_over_the_list(): void
    {
        config(['services.writing_help.model' => 'gemini-3.5-flash-lite', 'services.writing_help.price_input' => '0.075', 'services.writing_help.price_output' => '0.30']);
        $this->assertSame([0.075, 0.30], WritingHelpPrice::perMillion());
        $this->assertSame(0.000375, WritingHelpPrice::of(1000, 1000));

        config(['services.writing_help.model' => 'claude-haiku-5-5']);
        $this->assertSame([0.075, 0.30], WritingHelpPrice::perMillion());
    }

    public function test_an_unknown_price_costs_nothing_and_says_what_to_set(): void
    {
        config(['services.writing_help.provider' => 'gemini', 'services.writing_help.model' => 'gemini-9-ultra', 'services.writing_help.price_input' => '0.1', 'services.writing_help.price_output' => null]);

        $this->assertNull(WritingHelpPrice::perMillion());
        $this->assertSame(0.0, WritingHelpPrice::of(5000, 5000));
        $this->assertSame('No price known for gemini-9-ultra: set AI_PRICE_INPUT and AI_PRICE_OUTPUT (dollars per million tokens)', WritingHelpPrice::describe());
    }

    public function test_with_no_model_set_the_providers_cheapest_is_priced(): void
    {
        config(['services.writing_help.provider' => 'anthropic', 'services.writing_help.model' => null]);

        $this->assertSame([0.10, 0.50], WritingHelpPrice::perMillion());
        $this->assertSame('claude-haiku-5-5: $0.10 in, $0.50 out per million tokens', WritingHelpPrice::describe());
    }

    public function test_with_the_writing_help_off_it_says_so(): void
    {
        config(['services.writing_help.provider' => null]);

        $this->assertSame('Writing help is off (no AI_PROVIDER set); the figures are what it cost while it was on', WritingHelpPrice::describe());
    }

    public function test_a_small_bill_still_shows(): void
    {
        $this->assertSame('$0.00', WritingHelpPrice::format(0.0));
        $this->assertSame('$0.0016', WritingHelpPrice::format(0.0016));
        // A credit's worth or a translation's cost, to two digits that are not 0.
        $this->assertSame('$0.000065', WritingHelpPrice::format(0.000065));
        $this->assertSame('$0.0001', WritingHelpPrice::format(0.0001));
        $this->assertSame('$1.24', WritingHelpPrice::format(1.2361));
        $this->assertSame('$1,250.00', WritingHelpPrice::format(1250));
    }
}
