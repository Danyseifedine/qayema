<?php

namespace App\Services\Ai;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Closure;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Exceptions\AiException;
use RuntimeException;

/**
 * The dashboard's writing help: menu text put into the menu's other
 * language, and a category's or the restaurant's description written or
 * polished. Whether it runs is WritingHelpSetup's; one instance a request
 * (AppServiceProvider), so PaceWritingHelp can log what its calls spent.
 */
class WritingHelp
{
    /** How long a written category description is, in characters: at least, at most. */
    public const CATEGORY_LENGTH = [60, 70];

    /** How long a written restaurant description is, in characters: at least, at most. */
    public const RESTAURANT_LENGTH = [100, 110];

    /** Each description's length, by what the dashboard calls it. */
    public const LENGTHS = ['category' => self::CATEGORY_LENGTH, 'restaurant' => self::RESTAURANT_LENGTH];

    /** The longest menu text there is (a dish's ingredients, the restaurant's description). */
    public const LONGEST_TEXT = 2000;

    /** Tries for an answer that keeps the rules before giving up. */
    private const TRIES = 3;

    /**
     * Seconds a whole answer may take, every try together, so a slow
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

    /**
     * Claims a model likes to make up about food it has never seen, as
     * patterns. A description may make one only when what it was written
     * from does: a "Grills" category may say grilled, an owner who wrote
     * "homemade" keeps it. Arabic has no word boundaries to lean on, so its
     * words are matched anywhere.
     */
    private const UNSAID = [
        '\boven', '\bwood', '\bcharcoal', '\bgrill', '\bfresh', '\bhome ?made', '\bhand ?made',
        '\bhand stretched', '\bstretched', '\blocal', '\borganic', '\bchef', '\brecipe', '\bsince\b',
        '\baward', '\bauthentic', '\btraditional', '\bsecret', '\bfamous', '\bbest\b',
        'فرن', 'فحم', 'طازج', 'منزلي', 'شيف', 'وصفة', 'تقليدي', 'أصيل',
    ];

    private int $inputTokens = 0;

    private int $outputTokens = 0;

    /**
     * Text in one menu language put into another. Never longer than $max
     * (the field it goes into). When $fit names a description (category,
     * restaurant) and the text is one of that length, as the writing help
     * writes them, the translation is held to the same length and, like
     * every description, never has a dash; the owner's own text of any other
     * length keeps its dashes (a name like Coca-Cola).
     */
    public function translate(string $text, string $from, string $to, ?int $max = null, ?string $fit = null): string
    {
        $length = mb_strlen(trim($text));
        [$shortest, $longest] = self::LENGTHS[$fit] ?? [0, 0];
        $description = $length >= $shortest && $length <= $longest && $fit !== null;

        return $this->held(
            MenuTranslator::make(from: MenuLanguages::nameOf($from), to: MenuLanguages::nameOf($to)),
            fn (string $retry): string => $retry === '' ? $text : "Translate this text again:\n{$text}{$retry}",
            $description ? $shortest : 1,
            $description ? $longest : ($max ?? self::LONGEST_TEXT),
            description: $description,
            source: null,
        );
    }

    /** A category's line: a new one when $text is empty, else $text made better. */
    public function describeCategory(string $name, ?string $text, string $language): string
    {
        [$shortest, $longest] = self::CATEGORY_LENGTH;
        $prompt = filled($text)
            ? "Category: {$name}\nImprove this description:\n{$text}"
            : "Category: {$name}\nWrite its description.";

        return $this->held(
            CategoryDescriptionWriter::make(language: MenuLanguages::nameOf($language), shortest: $shortest, longest: $longest),
            fn (string $retry): string => $prompt.$retry,
            $shortest,
            $longest,
            description: true,
            source: $prompt,
        );
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

        return $this->held(
            RestaurantDescriptionWriter::make(language: MenuLanguages::nameOf($language), shortest: $shortest, longest: $longest),
            fn (string $retry): string => $prompt.$retry,
            $shortest,
            $longest,
            description: true,
            source: $prompt,
        );
    }

    /**
     * The tokens spent since the last time they were taken, the answers that
     * missed the rules included: what the usage log records. Counting then
     * starts afresh, so an app that lives past one request (the tests, a
     * worker) never logs a call twice.
     *
     * @return array{0: int, 1: int} input, output
     */
    public function takeTokens(): array
    {
        $tokens = [$this->inputTokens, $this->outputTokens];
        $this->inputTokens = $this->outputTokens = 0;

        return $tokens;
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
     * $agent's answer, held to the rules a model cannot be trusted to keep,
     * checked here: $shortest to $longest characters, and for a description
     * no dash and no claim its $source does not make (UNSAID). A miss is
     * asked again, told what was wrong, and TRIES misses are a failure,
     * never a broken rule. Every try shares TIME_BUDGET: each waits at most
     * what is left of it, and none starts with too little left.
     *
     * @param  Closure(string): string  $prompt  the prompt, given what was wrong with the last answer
     */
    private function held(Agent $agent, Closure $prompt, int $shortest, int $longest, bool $description, ?string $source): string
    {
        $retry = '';
        $deadline = now()->addSeconds(self::TIME_BUDGET);

        for ($try = 1; $try <= self::TRIES; $try++) {
            $left = (int) floor(now()->diffInSeconds($deadline, false));

            if ($left < self::SHORTEST_TRY) {
                break;
            }

            $wait = min($left, self::TRY_SECONDS);
            $answer = $this->ask(fn (): mixed => $agent->prompt($prompt($retry), timeout: $wait));
            $answer = $description ? self::withoutDashes($answer) : $answer;
            $length = mb_strlen($answer);
            $unsaid = $source === null ? null : self::unsaid($answer, $source);

            if ($unsaid === null && $length >= $shortest && $length <= $longest) {
                return $answer;
            }

            $retry = $unsaid !== null
                ? "\n\nYour last answer, \"{$answer}\", says \"{$unsaid}\", which nothing you were given says. Write it again without it."
                : "\n\nYour last answer, \"{$answer}\", was {$length} characters. ".($source === null ? 'Translate it again' : 'Write it again').' in '.self::lengthOf($shortest, $longest).'.';
        }

        throw new WritingHelpFailed(previous: new RuntimeException('No answer keeping the rules in time.'));
    }

    /** "60 to 70 characters", or "at most 300 characters" when only the end counts. */
    private static function lengthOf(int $shortest, int $longest): string
    {
        return $shortest > 1 ? "{$shortest} to {$longest} characters" : "at most {$longest} characters";
    }

    /** The first made-up claim in $answer that $source never makes, if any. */
    private static function unsaid(string $answer, string $source): ?string
    {
        foreach (self::UNSAID as $pattern) {
            $regex = '/'.$pattern.'/iu';

            if (preg_match($regex, $answer, $said) === 1 && preg_match($regex, $source) !== 1) {
                return $said[0];
            }
        }

        return null;
    }

    /**
     * Never a dash in what the menu prints: one between words becomes a
     * comma, a hyphen inside a word a space ("wood-fired" reads "wood fired").
     */
    public static function withoutDashes(string $text): string
    {
        // A run of dashes between words, however many and of whatever kind,
        // is one comma; what is left is hyphens inside words.
        $text = (string) preg_replace('/(?:\s*[\x{2012}-\x{2015}\x{2212}]\s*|\s+-+\s+)+/u', ', ', $text);
        $text = (string) preg_replace('/\s*-+\s*/', ' ', $text);

        // Nor does a line start or end on the comma a dash left there.
        return trim($text, " \t\n\r,");
    }

    /**
     * The `text` of the provider's answer, trimmed, its tokens counted. A
     * provider that is down, out of credit or refusing the key is one
     * failure to the owner (WritingHelpFailed), the cause logged; so is an
     * answer with no text, blank or not in the shape asked for.
     *
     * @param  callable(): mixed  $call  the agent's structured answer
     */
    private function ask(callable $call): string
    {
        try {
            $response = $call();
        } catch (AiException|RequestException $exception) {
            report($exception);

            throw new WritingHelpFailed(previous: $exception);
        }

        if (isset($response->usage)) {
            $this->inputTokens += $response->usage->inputTokens;
            $this->outputTokens += $response->usage->outputTokens;
        }

        $text = trim((string) ($response['text'] ?? ''));

        if ($text === '') {
            throw new WritingHelpFailed(previous: new RuntimeException('The provider answered with no text.'));
        }

        return $text;
    }
}
