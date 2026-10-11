<?php

namespace App\Services\Ai;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Exceptions\AiException;
use RuntimeException;

/**
 * The dashboard's writing help: menu text put into the menu's other
 * language, and a category's description written or polished. On only while
 * config('services.writing_help.provider') names a provider whose key is set,
 * so a server with no key simply has no AI buttons, and for a restaurant
 * whose package gives it uses (Feature::WritingHelpLimit, 0 for none).
 */
class WritingHelp
{
    /** How long a written category description is, in characters: at least, at most. */
    public const CATEGORY_LENGTH = [60, 70];

    /** How long a written restaurant description is, in characters: at least, at most. */
    public const RESTAURANT_LENGTH = [100, 110];

    /** Tries for a description of its length before giving up. */
    private const DESCRIPTION_TRIES = 3;

    /**
     * Seconds a whole description may take, every try together, so a slow
     * provider never holds a worker for long (PaceWritingHelp).
     */
    public const TIME_BUDGET = 15;

    /** Seconds one call may wait on the provider. */
    public const TRY_SECONDS = 10;

    /** A try with less time left than this is not started. */
    private const SHORTEST_TRY = 2;

    /** What of the menu a restaurant description is written from: enough to tell its kind of food. */
    private const MENU_CATEGORIES = 12;

    private const MENU_DISHES_EACH = 6;

    public static function isOn(): bool
    {
        $provider = self::provider();

        return $provider !== null && filled(config("ai.providers.{$provider}.key"));
    }

    /** On for this restaurant: on at all, and not 0 uses a day on its package. */
    public static function isOnFor(Restaurant $restaurant): bool
    {
        return self::isOn() && $restaurant->entitlements()->limit(Feature::WritingHelpLimit) !== 0;
    }

    public static function provider(): ?string
    {
        return config('services.writing_help.provider') ?: null;
    }

    /** Null leaves the agent on its provider's cheapest model. */
    public static function model(): ?string
    {
        return config('services.writing_help.model') ?: null;
    }

    public function translate(string $text, string $from, string $to): string
    {
        return $this->ask(fn (): mixed => MenuTranslator::make(
            from: MenuLanguages::nameOf($from),
            to: MenuLanguages::nameOf($to),
        )->prompt($text)['text']);
    }

    /** A category's line: a new one when $text is empty, else $text made better. */
    public function describeCategory(string $name, ?string $text, string $language): string
    {
        [$shortest, $longest] = self::CATEGORY_LENGTH;
        $prompt = filled($text)
            ? "Category: {$name}\nImprove this description:\n{$text}"
            : "Category: {$name}\nWrite its description.";

        return $this->written(CategoryDescriptionWriter::make(
            language: MenuLanguages::nameOf($language),
            shortest: $shortest,
            longest: $longest,
        ), $prompt, $shortest, $longest);
    }

    /**
     * The restaurant's own line, from its name and what its menu holds (the
     * saved one, in $language where written): a new one when $text is
     * empty, else $text made better. With nothing on the menu yet, a line
     * any restaurant could use.
     */
    public function describeRestaurant(Restaurant $restaurant, ?string $text, string $language): string
    {
        [$shortest, $longest] = self::RESTAURANT_LENGTH;
        $menu = $this->menuOf($restaurant, $language);
        $prompt = implode("\n", [
            'Restaurant: '.$restaurant->getTranslation('name', $language),
            $menu === '' ? 'Its menu is empty so far.' : "Its menu:\n{$menu}",
            filled($text) ? "Improve this description:\n{$text}" : 'Write its description.',
        ]);

        return $this->written(RestaurantDescriptionWriter::make(
            language: MenuLanguages::nameOf($language),
            shortest: $shortest,
            longest: $longest,
        ), $prompt, $shortest, $longest);
    }

    /** "Pizza: Margherita, Pepperoni" per category, the menu's order, a sample of each. */
    private function menuOf(Restaurant $restaurant, string $language): string
    {
        return $restaurant->categories()
            ->with(['dishes' => fn ($dishes) => $dishes->select(['id', 'category_id', 'name'])])
            ->limit(self::MENU_CATEGORIES)
            ->get(['id', 'name'])
            ->map(function (Category $category) use ($language): string {
                $dishes = $category->dishes
                    ->take(self::MENU_DISHES_EACH)
                    ->map(fn (Dish $dish): string => $dish->getTranslation('name', $language))
                    ->implode(', ');

                return $category->getTranslation('name', $language).($dishes === '' ? '' : ": {$dishes}");
            })
            ->implode("\n");
    }

    /**
     * $writer's answer to $prompt, $shortest to $longest characters and
     * without a dash. Models cannot count, so the length is checked here: a
     * miss is asked again, told how long it was, and DESCRIPTION_TRIES misses
     * are a failure, never a wrong length. Every try shares TIME_BUDGET: each
     * waits at most what is left of it, and none starts with too little left.
     */
    private function written(Agent $writer, string $prompt, int $shortest, int $longest): string
    {
        $retry = '';
        $deadline = now()->addSeconds(self::TIME_BUDGET);

        for ($try = 1; $try <= self::DESCRIPTION_TRIES; $try++) {
            $left = (int) floor(now()->diffInSeconds($deadline, false));

            if ($left < self::SHORTEST_TRY) {
                break;
            }

            $wait = min($left, self::TRY_SECONDS);
            $answer = self::withoutDashes($this->ask(fn (): mixed => $writer->prompt($prompt.$retry, timeout: $wait)['text']));
            $length = mb_strlen($answer);

            if ($length >= $shortest && $length <= $longest) {
                return $answer;
            }

            $retry = "\n\nYour last answer, \"{$answer}\", was {$length} characters. Write it again in {$shortest} to {$longest} characters.";
        }

        throw new WritingHelpFailed(previous: new RuntimeException("No description of {$shortest} to {$longest} characters in time."));
    }

    /**
     * Never a dash in what the menu prints: one between words becomes a
     * comma, a hyphen inside a word a space ("wood-fired" reads "wood fired").
     */
    public static function withoutDashes(string $text): string
    {
        $text = (string) preg_replace('/\s*[\x{2012}-\x{2015}\x{2212}]\s*|\s+-\s+/u', ', ', $text);

        return trim((string) preg_replace('/\s*-\s*/', ' ', $text));
    }

    /**
     * The provider's answer, trimmed. A provider that is down, out of credit
     * or refusing the key is one failure to the owner (WritingHelpFailed);
     * the cause goes to the log.
     *
     * @param  callable(): mixed  $call
     */
    private function ask(callable $call): string
    {
        try {
            $text = trim((string) $call());
        } catch (AiException|RequestException $exception) {
            report($exception);

            throw new WritingHelpFailed(previous: $exception);
        }

        if ($text === '') {
            throw new WritingHelpFailed(previous: new RuntimeException('The provider answered with no text.'));
        }

        return $text;
    }
}
