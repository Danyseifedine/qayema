<?php

namespace App\Ai\Agents;

use App\Ai\Agents\Concerns\UsesWritingHelpModel;
use App\Ai\Agents\Concerns\WritesVersions;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Promptable;
use Stringable;

/**
 * The line under a category's title on the menu: written from its name, or
 * the owner's own line made better.
 */
#[UseCheapestModel]
#[Temperature(0.4)]
class CategoryDescriptionWriter implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, UsesWritingHelpModel, WritesVersions;

    /**
     * $language is what the menu prints it in, as the menu lists it
     * ("العربية"); the length is WritingHelp::CATEGORY_LENGTH.
     */
    public function __construct(public string $language, public int $shortest, public int $longest) {}

    public function instructions(): Stringable|string
    {
        return <<<TEXT
        You write the short line printed under a category's title on a restaurant's menu, in {$this->language}.
        You know only what the owner tells you: the category's name and, sometimes, their own description. You know nothing else about this restaurant or its food.
        Write one sentence, inviting and plain. Give {$this->versionCount()} versions of it, each a whole sentence of its own, of rising length: {$this->versionsLine()}, counting spaces and punctuation.
        No dashes or hyphens of any kind, no emoji, quotation marks, hashtags or prices.
        Make it warm through the moment, never the making: a craving, sharing at the table, a start or an end to the meal, something for everyone. Do not open with "A selection of".
        Do not lean on stock phrases (with friends, at the table, with your loved ones, every occasion, on a high note); find the words that suit this category.
        To reach the length, add words about that moment, never about the food itself: no ingredients, cuts, textures, flavours or tastes (not juicy, crisp, tender, spicy, garden, greens) unless the owner wrote them.
        Say only what the name itself tells you. Never claim how, where or by whom the food is made, or anything about its quality or ingredients the owner did not write: no ovens, grills, wood fire, charcoal, homemade, handmade, hand-stretched, fresh, local, organic, secret recipes, family traditions, chefs, origins or awards. Those words are fine only when the owner wrote them.
        Good, for "Pizza" (67 characters): Our pizzas, from timeless classics to new favourites for the table.
        Bad, for "Pizza": Hand-stretched dough baked in our wood-fired oven. (It invents how they are made.)
        When given the owner's description, every fact in it must be in your answer (what, how, where, when, with what): drop none, add none, only fix and polish the wording. Their facts come before any words about the moment.
        The examples show the kind of line wanted; never copy them word for word.
        Answer with `texts`: the versions, shortest first, in {$this->language}.
        TEXT;
    }
}
