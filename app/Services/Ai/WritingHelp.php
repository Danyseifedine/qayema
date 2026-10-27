<?php

namespace App\Services\Ai;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuListTranslator;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Support\MenuTextLimits;
use Closure;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Carbon;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
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

    /** The longest menu text there is (a dish's ingredients). */
    public const LONGEST_TEXT = MenuTextLimits::LONGEST;

    /** Tries for an answer that keeps the rules before giving up. */
    private const TRIES = 3;

    /**
     * Seconds a whole answer may take, every try together, so a slow
     * provider never holds a worker for long (PaceWritingHelp).
     */
    public const TIME_BUDGET = 15;

    /** Seconds one call may wait on the provider. */
    public const TRY_SECONDS = 10;

    /**
     * A list (translateMany) is one answer of many pieces, so it gets
     * longer: seconds for the whole of it, and for one call. Still inside
     * PaceWritingHelp's hold.
     */
    public const LIST_TIME_BUDGET = 22;

    public const LIST_TRY_SECONDS = 15;

    /** The most pieces one list carries: everything one dish can hold. */
    public const LIST_ITEMS = 100;

    /**
     * Their characters together, so one list stays one quick answer: a
     * dish's name, its ingredients and 75 choices (5 variants of 10 options
     * and 20 add-ons, config/menu.php), each as long as it may be.
     */
    public const LIST_LENGTH = MenuTextLimits::DISH_NAME + MenuTextLimits::INGREDIENTS + 75 * MenuTextLimits::CHOICE_NAME;

    /**
     * The most tokens a prompt carries besides its own text: the agent's
     * instructions and the answer's JSON format (measured at about 400 for
     * a translation and 900 for a description), with room to spare.
     */
    private const PROMPT_TOKENS = 1500;

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
        $longest = $description ? $longest : ($max ?? self::LONGEST_TEXT);

        return $this->held(
            MenuTranslator::make(from: MenuLanguages::nameOf($from), to: MenuLanguages::nameOf($to), characters: $longest),
            fn (string $retry): string => $retry === '' ? $text : "Translate this text again:\n{$text}{$retry}",
            $description ? $shortest : 1,
            $longest,
            description: $description,
            source: null,
        );
    }

    /**
     * Many pieces of menu text at once, each into its own language: what a
     * dish's save fills in for every name, ingredient list and choice
     * written in one language only. Pieces going the same way are one call,
     * each translation keeps to its own `max`, and the answers come back in
     * the order given.
     *
     * @param  list<array{text: string, from: string, to: string, max?: int|null}>  $items
     * @return list<string>
     */
    public function translateMany(array $items): array
    {
        $deadline = now()->addSeconds(self::LIST_TIME_BUDGET);
        $answers = [];

        foreach (collect($items)->groupBy(fn (array $item): string => "{$item['from']}>{$item['to']}", preserveKeys: true) as $group) {
            $first = $group->first();
            $maxes = $group->map(fn (array $item): int => $item['max'] ?? self::LONGEST_TEXT)->values()->all();
            $translated = $this->list(
                MenuListTranslator::make(
                    from: MenuLanguages::nameOf($first['from']),
                    to: MenuLanguages::nameOf($first['to']),
                    characters: array_sum($maxes),
                    pieces: count($maxes),
                ),
                $group->pluck('text')->values()->all(),
                $maxes,
                $deadline,
            );

            foreach ($group->keys()->values() as $position => $index) {
                $answers[$index] = $translated[$position];
            }
        }

        ksort($answers);

        return array_values($answers);
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
            'Restaurant: '.mb_substr($restaurant->getTranslation('name', $language), 0, MenuTextLimits::RESTAURANT_NAME),
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
     * The most one request to $route could cost, in dollars, read from what
     * it sends before it is checked (so kept to what the checks allow):
     * every try at its answer's cap, each sent its prompt with the last
     * answer back. What WritingHelpCredits holds back so the real cost never
     * passes what a restaurant's credits are worth. 0 for a model with no
     * known price, and for any other route.
     *
     * @param  array<string, mixed>  $input
     */
    public function mostItCouldCost(string $route, array $input): float
    {
        $prices = WritingHelpPrice::perMillion();

        [$characters, $answerTokens] = match ($route) {
            'api.writing.translate' => $this->translationSize($input),
            'api.writing.translate-many' => $this->listSize($input),
            'api.writing.category-description' => [
                MenuTextLimits::CATEGORY_NAME + MenuTextLimits::CATEGORY_DESCRIPTION,
                CategoryDescriptionWriter::make(language: '', shortest: self::CATEGORY_LENGTH[0], longest: self::CATEGORY_LENGTH[1])->maxTokens(),
            ],
            'api.writing.restaurant-description' => [
                // Its name, its own text and the part of its menu it reads.
                MenuTextLimits::RESTAURANT_NAME + MenuTextLimits::RESTAURANT_DESCRIPTION
                    + self::MENU_CATEGORIES * (MenuTextLimits::CATEGORY_NAME + self::MENU_DISHES_EACH * (MenuTextLimits::DISH_NAME + 2)),
                RestaurantDescriptionWriter::make(language: '', shortest: self::RESTAURANT_LENGTH[0], longest: self::RESTAURANT_LENGTH[1])->maxTokens(),
            ],
            default => [0, 0],
        };

        if ($prices === null || $answerTokens === 0) {
            return 0.0;
        }

        $inputTokens = self::PROMPT_TOKENS + 2 * $characters + $answerTokens;

        return self::TRIES * ($inputTokens * $prices[0] + $answerTokens * $prices[1]) / 1_000_000;
    }

    /**
     * One text's characters and its answer's cap, as translate() asks.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: int, 1: int}
     */
    private function translationSize(array $input): array
    {
        $characters = self::fitted(mb_strlen(is_string($input['text'] ?? null) ? $input['text'] : ''));
        // A description keeps its own length, any other text its field's
        // limit: whichever is longer, since which applies hangs on the answer.
        $fit = is_string($input['fit'] ?? null) ? $input['fit'] : '';
        $longest = max(self::fitted($input['max'] ?? null), self::LENGTHS[$fit][1] ?? 0);

        return [$characters, MenuTranslator::make(from: '', to: '', characters: max($characters, $longest))->maxTokens()];
    }

    /**
     * A list's characters and its answers' cap, as translateMany() asks: one
     * call each way the pieces go, their limits together no more than the
     * request's checks allow.
     *
     * @param  array<string, mixed>  $input
     * @return array{0: int, 1: int}
     */
    private function listSize(array $input): array
    {
        $items = array_slice(array_values(array_filter((array) ($input['items'] ?? []), is_array(...))), 0, self::LIST_ITEMS);
        $characters = 0;
        $answerTokens = 0;

        foreach (collect($items)->groupBy(fn (array $item): string => json_encode([$item['from'] ?? null, $item['to'] ?? null]) ?: '') as $group) {
            $texts = $group->sum(fn (array $item): int => self::fitted(mb_strlen(is_string($item['text'] ?? null) ? $item['text'] : '')));
            $maxes = $group->sum(fn (array $item): int => self::fitted($item['max'] ?? null));
            $characters += $texts;
            $answerTokens += MenuListTranslator::make(from: '', to: '', characters: min($maxes, self::LIST_LENGTH), pieces: $group->count())->maxTokens();
        }

        return [min($characters, self::LIST_LENGTH), $answerTokens];
    }

    /** A length or a limit as the checks keep it: 1 to LONGEST_TEXT, LONGEST_TEXT when none is given. */
    private static function fitted(mixed $length): int
    {
        return is_numeric($length) ? max(1, min((int) $length, self::LONGEST_TEXT)) : self::LONGEST_TEXT;
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

    /**
     * "Pizza: Margherita, Pepperoni" per category, the menu's order, a sample
     * of each, every name cut to today's limit (text saved under the old,
     * longer ones may be longer), so the prompt is never more than
     * mostItCouldCost() counted on.
     */
    private function menuOf(Restaurant $restaurant, string $language): string
    {
        return $restaurant->categories()
            ->with(['dishes' => fn ($dishes) => $dishes->select(['id', 'category_id', 'name'])])
            ->limit(self::MENU_CATEGORIES)
            ->get(['id', 'name'])
            ->map(function (Category $category) use ($language): string {
                $dishes = $category->dishes
                    ->take(self::MENU_DISHES_EACH)
                    ->map(fn (Dish $dish): string => mb_substr($dish->getTranslation('name', $language), 0, MenuTextLimits::DISH_NAME))
                    ->implode(', ');

                return mb_substr($category->getTranslation('name', $language), 0, MenuTextLimits::CATEGORY_NAME).($dishes === '' ? '' : ": {$dishes}");
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
            $answers = $this->ask($agent, fn (): mixed => $agent->prompt($prompt($retry), timeout: $wait));
            $answers = $description ? array_map(self::withoutDashes(...), $answers) : $answers;
            $misses = [];

            // The first that keeps every rule: a description comes as several
            // versions of rising length (WritesVersions), a translation as one.
            foreach ($answers as $answer) {
                $length = mb_strlen($answer);
                $unsaid = $source === null ? null : self::unsaid($answer, $source);

                if ($unsaid === null && $length >= $shortest && $length <= $longest) {
                    return $answer;
                }

                $misses[] = [$answer, $length, $unsaid];
            }

            $retry = self::whatWasWrong($misses, $shortest, $longest, $source === null);
        }

        throw new WritingHelpFailed(previous: new RuntimeException('No answer keeping the rules in time.'));
    }

    /**
     * $agent's list for $texts, held to what a model cannot be trusted to
     * keep: one translation a text, none empty, each within its $maxes. A
     * miss is asked again, told what was wrong, within the list's deadline.
     *
     * @param  list<string>  $texts
     * @param  list<int>  $maxes
     * @return list<string>
     */
    private function list(Agent $agent, array $texts, array $maxes, Carbon $deadline): array
    {
        $prompt = (string) json_encode($texts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $retry = '';

        for ($try = 1; $try <= self::TRIES; $try++) {
            $left = (int) floor(now()->diffInSeconds($deadline, false));

            if ($left < self::SHORTEST_TRY) {
                break;
            }

            $wait = min($left, self::LIST_TRY_SECONDS);
            $response = $this->respond($agent, fn (): mixed => $agent->prompt($prompt.$retry, timeout: $wait));
            $answers = array_map(
                fn (mixed $answer): string => is_string($answer) ? trim($answer) : '',
                array_values((array) ($response['texts'] ?? [])),
            );
            $problem = self::listProblem($answers, $maxes);

            if ($problem === null) {
                return $answers;
            }

            $retry = "\n\n{$problem}";
        }

        throw new WritingHelpFailed(previous: new RuntimeException('No list keeping the rules in time.'));
    }

    /**
     * What is wrong with a list's answers, told the way the model is asked
     * again; null when nothing is.
     *
     * @param  list<string>  $answers
     * @param  list<int>  $maxes
     */
    private static function listProblem(array $answers, array $maxes): ?string
    {
        $wanted = count($maxes);

        if (count($answers) !== $wanted) {
            return 'Your last answer had '.count($answers)." translations for {$wanted} texts. Answer with exactly {$wanted}, one for each text, in the same order.";
        }

        foreach ($answers as $index => $answer) {
            $number = $index + 1;
            $length = mb_strlen($answer);

            if ($length === 0) {
                return "Translation {$number} of your last answer was empty. Translate every text.";
            }

            if ($length > $maxes[$index]) {
                return "Translation {$number} of your last answer, \"{$answer}\", was {$length} characters. Keep it to at most {$maxes[$index]}.";
            }
        }

        return null;
    }

    /**
     * What was wrong with the last answer, told the way the model is asked
     * again: the claim nothing given makes, else the length one answer
     * missed, else the lengths none of the versions kept.
     *
     * @param  non-empty-list<array{0: string, 1: int, 2: string|null}>  $misses  each answer, its length, its unsaid claim
     */
    private static function whatWasWrong(array $misses, int $shortest, int $longest, bool $translation): string
    {
        foreach ($misses as [$answer, , $unsaid]) {
            if ($unsaid !== null) {
                return "\n\nYour last answer, \"{$answer}\", says \"{$unsaid}\", which nothing you were given says. Write it again without it.";
            }
        }

        if (count($misses) === 1) {
            [$answer, $length] = $misses[0];

            return "\n\nYour last answer, \"{$answer}\", was {$length} characters. ".($translation ? 'Translate it again' : 'Write it again').' in '.self::lengthOf($shortest, $longest).'.';
        }

        $lengths = implode(', ', array_column($misses, 1));

        return "\n\nYour last versions were {$lengths} characters long; none was ".self::lengthOf($shortest, $longest).'. Write them again, spread more evenly around that length.';
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
     * The provider's answer, its tokens counted. A provider that is down,
     * out of credit or refusing the key is one failure to the owner
     * (WritingHelpFailed), the cause logged. A call cut off on its way (a
     * timeout) may still be billed for what it wrote, so it counts as the
     * most it could have cost: the credits' cap never trusts it was free.
     *
     * @param  callable(): mixed  $call  the agent's structured answer
     */
    private function respond(Agent $agent, callable $call): mixed
    {
        try {
            $response = $call();
        } catch (ProviderConnectionException $exception) {
            $this->inputTokens += self::PROMPT_TOKENS;
            $this->outputTokens += method_exists($agent, 'maxTokens') ? (int) $agent->maxTokens() : 0;
            report($exception);

            throw new WritingHelpFailed(previous: $exception);
        } catch (AiException|RequestException $exception) {
            report($exception);

            throw new WritingHelpFailed(previous: $exception);
        }

        if (isset($response->usage)) {
            $this->inputTokens += $response->usage->inputTokens;
            $this->outputTokens += $response->usage->outputTokens;
        }

        return $response;
    }

    /**
     * The provider's answer (respond) as its texts, trimmed and none blank:
     * a writer's `texts` (its versions) or an agent's one `text`. An answer
     * with no text, blank or not in the shape asked for, is a failure too.
     *
     * @param  callable(): mixed  $call  the agent's structured answer
     * @return non-empty-list<string>
     */
    private function ask(Agent $agent, callable $call): array
    {
        $response = $this->respond($agent, $call);
        $texts = is_array($response['texts'] ?? null) ? $response['texts'] : [$response['text'] ?? ''];
        $texts = array_values(array_filter(
            array_map(fn (mixed $text): string => is_string($text) ? trim($text) : '', $texts),
            fn (string $text): bool => $text !== '',
        ));

        if ($texts === []) {
            throw new WritingHelpFailed(previous: new RuntimeException('The provider answered with no text.'));
        }

        return $texts;
    }
}
