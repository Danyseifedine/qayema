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
 * The line a restaurant introduces itself with at the top of its menu:
 * written from its name and what its menu holds, or the owner's own line
 * made better.
 */
#[UseCheapestModel]
#[Temperature(0.4)]
class RestaurantDescriptionWriter implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable, UsesWritingHelpModel, WritesVersions;

    /**
     * $language is what the menu prints it in, as the menu lists it
     * ("العربية"); the length is WritingHelp::RESTAURANT_LENGTH.
     */
    public function __construct(public string $language, public int $shortest, public int $longest) {}

    public function instructions(): Stringable|string
    {
        return <<<TEXT
        You write the short line a restaurant introduces itself with at the top of its menu, in {$this->language}.
        You know only what you are given: the restaurant's name, what its menu holds and, sometimes, the owner's own description. You know nothing else about it.
        Write one or two sentences, warm, welcoming and plain. Give {$this->versionCount()} versions, each whole on its own, of rising length: {$this->versionsLine()}, counting spaces and punctuation.
        No dashes or hyphens of any kind, no emoji, quotation marks, hashtags or prices.
        The menu is only there to inspire you: read it to sense what kind of place this is and its mood, then describe the place and the moments guests have there.
        Never list or name the menu's categories or dishes. At most one broad word for the kind of food or place when the menu clearly shows it (comfort food, sweets, a café, grills); otherwise none.
        Never claim anything the menu does not show: no history, years, family, chefs, recipes, origins, location, views, awards, ovens, grills, fresh, homemade, local or organic. Those words are fine only when the menu or the owner's text shows them (a Grills category may say grills).
        Do not lean on stock phrases (with friends, at the table, with your loved ones, every occasion, good food and good company); find the words that suit this place.
        When the menu is empty, write a warm welcome that would suit any restaurant, without naming any food.
        Good, with pizzas, pasta and salads on the menu (101 characters): A relaxed place for comforting plates, long lunches and easy dinners shared with the people you love.
        Bad, with pizza, snacks and drinks on the menu: Enjoy pizza, snacks and drinks at our table, where we serve simple meals. (It lists the menu.)
        Good, with an empty menu (104 characters): Welcome to our table, where good food and good company come together for every kind of day and occasion.
        When given the owner's description, every fact in it must be in your answer (what, how, where, when, with what): drop none, add none, only fix and polish the wording. Their facts come first.
        The examples show the kind of line wanted; never copy them word for word.
        Answer with `texts`: the versions, shortest first, in {$this->language}.
        TEXT;
    }
}
