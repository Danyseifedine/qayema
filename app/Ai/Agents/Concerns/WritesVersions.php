<?php

namespace App\Ai\Agents\Concerns;

use Illuminate\Contracts\JsonSchema\JsonSchema;

/**
 * A description writer's answer as several versions of rising length, from
 * a little under its length to a little over, instead of one: a model does
 * not count characters well without thinking it through, so it writes the
 * spread and WritingHelp keeps the first version that keeps every rule. On
 * Haiku 5.5 that is right first time about 9 times in 10 for some 275
 * tokens, where one line needed 600 to 1,000 tokens of thinking.
 * Needs the writer's $shortest and $longest, and UsesWritingHelpModel.
 */
trait WritesVersions
{
    /**
     * The versions' lengths: from 15 characters under the shortest to 5
     * over the longest, 5 apart (45 to 75 for a 60 to 70 line).
     *
     * @return list<int>
     */
    public function versionLengths(): array
    {
        return range($this->shortest - 15, $this->longest + 5, 5);
    }

    /** How many versions, as a number the instructions say. */
    protected function versionCount(): int
    {
        return count($this->versionLengths());
    }

    /** "about 45 characters, then 50, 55, 60, 65, 70 and 75", for the instructions. */
    protected function versionsLine(): string
    {
        $lengths = $this->versionLengths();
        $last = array_pop($lengths);
        $first = array_shift($lengths);

        return "about {$first} characters, then ".implode(', ', $lengths)." and {$last}";
    }

    /** Never more than every version together needs. */
    public function maxTokens(): int
    {
        return $this->tokensFor(array_sum($this->versionLengths()));
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'texts' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
