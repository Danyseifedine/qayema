<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\UsesWritingHelpModel;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/** One piece of a restaurant's menu text, put into the menu's other language. */
#[UseCheapestModel]
#[MaxTokens(2000)]
#[Temperature(0.2)]
class MenuTranslator implements Agent, HasStructuredOutput
{
    use Promptable, UsesWritingHelpModel;

    /** Both are language names as the menu lists them ("Français"). */
    public function __construct(public string $from, public string $to) {}

    public function instructions(): Stringable|string
    {
        return <<<TEXT
        You translate a restaurant's menu text from {$this->from} into {$this->to}.
        The text is a dish or category name, a description, a list of ingredients or the restaurant's own name or description.
        Write it the way a restaurant in that language would print it on its menu: natural, short and appetising.
        Dishes known by their own name (shawarma, tabbouleh, pizza, sushi) keep it, written the way diners in {$this->to} know it.
        Keep numbers, sizes and units as they are. Add nothing, explain nothing, and leave nothing out.
        Answer with the translation only.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'text' => $schema->string()->required(),
        ];
    }
}
