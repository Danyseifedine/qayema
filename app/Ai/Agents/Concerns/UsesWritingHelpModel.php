<?php

namespace App\Ai\Agents\Concerns;

use App\Services\Ai\WritingHelpSetup;
use Laravel\Ai\Enums\Lab;

/**
 * The provider and model the writing help runs on, from the config, so
 * changing either is an .env edit. A null model falls back to the agent's
 * #[UseCheapestModel].
 */
trait UsesWritingHelpModel
{
    public function provider(): ?string
    {
        return WritingHelpSetup::provider();
    }

    public function model(): ?string
    {
        return WritingHelpSetup::model();
    }

    /**
     * Claude thinks before it answers by default, and its thinking counts
     * against the answer's tokens: hundreds of them for a line of a dozen
     * words. None of the writing help needs it (a translation reads the same
     * without, a description is written as several versions to pick from,
     * WritesVersions), so on Haiku (set, or the provider's cheapest) thinking
     * is off. Claude Opus 5.5 and Sonnet 5.5 refuse thinking off, so any
     * other Claude model thinks at low effort, which every current one
     * accepts. Other providers get nothing extra.
     *
     * @return array<string, mixed>
     */
    public function providerOptions(Lab|string $provider): array
    {
        if (($provider instanceof Lab ? $provider->value : $provider) !== Lab::Anthropic->value) {
            return [];
        }

        return self::onHaiku()
            ? ['thinking' => ['type' => 'disabled']]
            : ['output_config' => ['effort' => 'low']];
    }

    /**
     * The most tokens an answer of $characters may take, the cap on what
     * one call can cost: two tokens a character covers every script a menu
     * is written in, plus room for the answer's JSON, plus what a model that
     * may think first spends on that. Only Haiku is known not to (thinking
     * is off above); any other Claude model thinks at low effort, and
     * another provider's model may think by default, which counts against
     * the same cap and would leave no room for the answer.
     */
    protected function tokensFor(int $characters): int
    {
        return 2 * $characters + 100 + (self::onHaiku() ? 0 : 1000);
    }

    /** Whether the writing help runs on Claude Haiku, set or the provider's cheapest. */
    private static function onHaiku(): bool
    {
        return str_starts_with((string) WritingHelpSetup::effectiveModel(), 'claude-haiku-');
    }
}
