<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Stringable;

/**
 * Many pieces of a restaurant's menu text at once (a dish's name, its
 * ingredients and every choice), put into the menu's other language: the
 * same rules as one piece, answered as a list in the order given.
 */
#[UseCheapestModel]
#[Temperature(0.2)]
class MenuListTranslator extends MenuTranslator
{
    /**
     * $characters is every answer's limit added up, for $pieces texts:
     * each is a few tokens more of the list's JSON.
     */
    public function __construct(string $from, string $to, int $characters, public int $pieces)
    {
        parent::__construct($from, $to, $characters);
    }

    public function maxTokens(): int
    {
        return $this->tokensFor($this->characters) + 5 * $this->pieces;
    }

    public function instructions(): Stringable|string
    {
        return <<<TEXT
        You translate a list of a restaurant's menu texts from {$this->from} into {$this->to}.
        Each text is a dish name, a list of ingredients, or a choice on a dish such as a size, a spice level or an extra.
        Write each the way a restaurant in that language would print it on its menu: natural, short and appetising.
        Dishes known by their own name (shawarma, tabbouleh, pizza, sushi) keep it, written the way diners in {$this->to} know it.
        Keep numbers, sizes and units as they are. Add nothing, explain nothing, and leave nothing out.
        The texts come as a JSON list. Answer with `texts`: one translation for each text, in the same order, nothing else.
        TEXT;
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'texts' => $schema->array()->items($schema->string())->required(),
        ];
    }
}
