<?php

namespace Tests\Integration\Services\Ai;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Models\Category;
use App\Models\Dish;
use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpFailed;
use App\Services\Ai\WritingHelpSetup;
use GuzzleHttp\Psr7\Response as Psr7Response;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Exceptions;
use Laravel\Ai\Exceptions\AiException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\Data\Meta;
use Laravel\Ai\Responses\Data\TextUsage;
use Laravel\Ai\Responses\StructuredTextResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * App\Services\Ai\WritingHelp on its own: when it is on, what it sends the
 * provider, and the rules it holds every answer to.
 */
class WritingHelpTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const CATEGORY_LINE = 'Warm bowls of soup to start your meal or share across the table.';

    private const RESTAURANT_LINE = 'Welcome to our table, where good food and good company come together for every kind of day and occasion.';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.writing_help.provider' => 'anthropic',
            'services.writing_help.model' => null,
            'ai.providers.anthropic.key' => 'test-key',
        ]);
    }

    private function help(): WritingHelp
    {
        return app(WritingHelp::class);
    }

    // ---- On and off ------------------------------------------------------

    /** @return array<string, array{0: string|null, 1: string|null, 2: bool}> */
    public static function setups(): array
    {
        return [
            'a provider with its key' => ['anthropic', 'test-key', true],
            'no provider' => [null, 'test-key', false],
            'an empty provider' => ['', 'test-key', false],
            'a provider without its key' => ['anthropic', null, false],
            'a provider with an empty key' => ['anthropic', '', false],
            'a provider the config does not know' => ['nobody', 'test-key', false],
        ];
    }

    #[DataProvider('setups')]
    public function test_it_is_on_only_with_a_provider_and_its_key(?string $provider, ?string $key, bool $on): void
    {
        config(['services.writing_help.provider' => $provider, 'ai.providers.anthropic.key' => $key]);

        $this->assertSame($on, WritingHelpSetup::isOn());
    }

    public function test_an_empty_provider_or_model_reads_as_none(): void
    {
        config(['services.writing_help.provider' => '', 'services.writing_help.model' => '']);

        $this->assertNull(WritingHelpSetup::provider());
        $this->assertNull(WritingHelpSetup::model());

        config(['services.writing_help.provider' => 'gemini', 'services.writing_help.model' => 'gemini-3.5-flash-lite']);

        $this->assertSame('gemini', WritingHelpSetup::provider());
        $this->assertSame('gemini-3.5-flash-lite', WritingHelpSetup::model());
    }

    /** @return array<string, array{0: float|null, 1: bool}> */
    public static function packageBudgets(): array
    {
        return [
            'no AI budget, so no credits' => [0, false],
            'a few cents a month' => [0.05, true],
            'no cap on its AI' => [null, true],
        ];
    }

    #[DataProvider('packageBudgets')]
    public function test_for_a_restaurant_it_also_needs_credits_on_the_package(?float $cents, bool $on): void
    {
        $this->defaultPackageAiBudget($cents);

        $this->assertSame($on, WritingHelpSetup::isOnFor($this->owner()));
    }

    public function test_no_package_brings_it_back_on_a_server_without_a_key(): void
    {
        $this->defaultPackageAiBudget(null);
        config(['ai.providers.anthropic.key' => null]);

        $this->assertFalse(WritingHelpSetup::isOnFor($this->owner()));
    }

    // ---- Translating -----------------------------------------------------

    public function test_a_translation_names_both_languages_as_the_menu_lists_them_and_is_trimmed(): void
    {
        MenuTranslator::fake([['text' => "  Soupe du jour \n"]]);

        $this->assertSame('Soupe du jour', $this->help()->translate('Soup of the day', 'en', 'fr'));

        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Soup of the day'
            && $prompt->agent->from === 'English'
            && $prompt->agent->to === 'Français');
    }

    public function test_a_translation_keeps_its_dashes_since_it_is_the_owners_own_text(): void
    {
        // Only written descriptions are held to "no dash"; a name like
        // Coca-Cola stays as the owner spelled it.
        MenuTranslator::fake([['text' => 'كوكا-كولا']]);

        $this->assertSame('كوكا-كولا', $this->help()->translate('Coca-Cola', 'en', 'ar'));
    }

    // ---- What a restaurant's line is written from ------------------------

    public function test_the_menu_is_read_in_its_order_a_sample_of_each_and_only_this_restaurants(): void
    {
        RestaurantDescriptionWriter::fake([['text' => self::RESTAURANT_LINE]]);
        $owner = $this->owner(['name' => ['en' => 'Olive']]);
        $other = $this->owner();
        Category::factory()->create(['restaurant_id' => $other->id, 'name' => ['en' => 'Not mine'], 'display_order' => 0]);

        // Out of order on purpose; the menu's order is display_order.
        $second = Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => 'Drinks'], 'display_order' => 2]);
        $first = Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => 'Pizza'], 'display_order' => 1]);
        Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => 'Empty'], 'display_order' => 3]);
        foreach (range(1, 7) as $n) {
            Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $first->id, 'name' => ['en' => "Pizza {$n}"], 'display_order' => $n]);
        }
        Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $second->id, 'name' => ['en' => 'Cola'], 'display_order' => 1]);

        $this->help()->describeRestaurant($owner, null, 'en');

        RestaurantDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === implode("\n", [
            'Restaurant: Olive',
            'Its menu:',
            // Six dishes a category at most.
            'Pizza: Pizza 1, Pizza 2, Pizza 3, Pizza 4, Pizza 5, Pizza 6',
            'Drinks: Cola',
            // A category with no dishes is still its name.
            'Empty',
            'Write its description.',
        ]));
    }

    public function test_at_most_twelve_categories_are_read(): void
    {
        RestaurantDescriptionWriter::fake([['text' => self::RESTAURANT_LINE]]);
        $owner = $this->owner(['name' => ['en' => 'Olive']]);
        foreach (range(1, 13) as $n) {
            Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => "Section {$n}"], 'display_order' => $n]);
        }

        $this->help()->describeRestaurant($owner, null, 'en');

        RestaurantDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains("Section 12\nWrite")
            && ! $prompt->contains('Section 13'));
    }

    public function test_the_menu_is_read_in_the_language_written_falling_back_to_the_main_one(): void
    {
        RestaurantDescriptionWriter::fake([['text' => self::RESTAURANT_LINE]]);
        $owner = $this->owner(['name' => ['en' => 'Olive', 'ar' => 'زيتون'], 'second_locale' => 'ar']);
        $category = Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => 'Grills', 'ar' => 'مشاوي']]);
        Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id, 'name' => ['en' => 'Kafta']]);

        $this->help()->describeRestaurant($owner, 'مشاوي على الفحم', 'ar');

        RestaurantDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === "Restaurant: زيتون\nIts menu:\nمشاوي: Kafta\nImprove this description:\nمشاوي على الفحم"
            && $prompt->agent->language === 'العربية');
    }

    // ---- Lengths and dashes ----------------------------------------------

    /** @return array<string, array{0: string, 1: bool}> */
    public static function categoryLengths(): array
    {
        return [
            '59 characters is too short' => [str_repeat('a', 59), false],
            '60 characters fits' => [str_repeat('a', 60), true],
            '70 characters fits' => [str_repeat('a', 70), true],
            '71 characters is too long' => [str_repeat('a', 71), false],
        ];
    }

    #[DataProvider('categoryLengths')]
    public function test_a_category_line_is_60_to_70_characters_both_ends_included(string $answer, bool $fits): void
    {
        CategoryDescriptionWriter::fake([['text' => $answer], ['text' => $answer], ['text' => $answer]]);

        if (! $fits) {
            $this->expectException(WritingHelpFailed::class);
        }

        $this->assertSame($answer, $this->help()->describeCategory('Soups', null, 'en'));
    }

    /** @return array<string, array{0: int, 1: bool}> */
    public static function restaurantLengths(): array
    {
        return [
            '99 characters is too short' => [99, false],
            '100 characters fits' => [100, true],
            '110 characters fits' => [110, true],
            '111 characters is too long' => [111, false],
        ];
    }

    #[DataProvider('restaurantLengths')]
    public function test_a_restaurant_line_is_100_to_110_characters_both_ends_included(int $length, bool $fits): void
    {
        $answer = str_repeat('a', $length);
        RestaurantDescriptionWriter::fake([['text' => $answer], ['text' => $answer], ['text' => $answer]]);

        if (! $fits) {
            $this->expectException(WritingHelpFailed::class);
        }

        $this->assertSame($answer, $this->help()->describeRestaurant($this->owner(), null, 'en'));
    }

    public function test_arabic_is_counted_in_letters_not_bytes(): void
    {
        // 62 letters, well over 70 bytes.
        $arabic = 'أطباق تجمع الأهل والأصدقاء حول المائدة في كل لحظة سعيدة ومميزة';
        $this->assertSame(62, mb_strlen($arabic));
        CategoryDescriptionWriter::fake([['text' => $arabic]]);

        $this->assertSame($arabic, $this->help()->describeCategory('مشاوي', null, 'ar'));
    }

    public function test_dashes_go_before_the_length_is_counted(): void
    {
        // 71 with its spaced em dash, 70 once it is a comma: it fits.
        $answer = 'Warm bowls of soup to start your meal '."\u{2014}".' or share it across a long table';
        $this->assertSame(71, mb_strlen($answer));
        CategoryDescriptionWriter::fake([['text' => $answer]]);

        $this->assertSame('Warm bowls of soup to start your meal, or share it across a long table', $this->help()->describeCategory('Soups', null, 'en'));
        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('Your last answer'));
    }

    public function test_each_retry_quotes_the_last_answer_and_its_length(): void
    {
        CategoryDescriptionWriter::fake([['text' => 'Soups.'], ['text' => 'Soups to start.'], ['text' => self::CATEGORY_LINE]]);

        $this->assertSame(self::CATEGORY_LINE, $this->help()->describeCategory('Soups', null, 'en'));

        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Your last answer, "Soups.", was 6 characters. Write it again in 60 to 70 characters.'));
        // Only the latest miss is quoted, never a growing list.
        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('"Soups to start.", was 15 characters')
            && ! $prompt->contains('"Soups.", was 6'));
    }

    // ---- Claims nothing backs ---------------------------------------------

    /** @return array<string, array{0: string, 1: string}> */
    public static function madeUp(): array
    {
        return [
            'an oven' => ['Thin pizzas baked in our oven, made for long and easy evenings.', 'oven'],
            'fresh' => ['Fresh pizzas to share around a long table on a slow, easy evening.', 'Fresh'],
            'homemade' => ['Homemade pizzas to share around a long table on a slow evening.', 'Homemade'],
            'a chef' => ['Pizzas our chef loves to share around a long table every evening.', 'chef'],
            'a year' => ['Serving pizzas since forever, to share on long and easy evenings.', 'since'],
            'a boast' => ['The best pizzas to share around a long table on a slow evening.', 'best'],
            'in Arabic' => ['بيتزا طازجة تجمع الأهل والأصدقاء حول مائدة واحدة في كل الأوقات.', 'طازج'],
        ];
    }

    #[DataProvider('madeUp')]
    public function test_a_claim_nothing_backs_is_asked_again_by_its_word(string $answer, string $word): void
    {
        CategoryDescriptionWriter::fake([['text' => $answer], ['text' => self::CATEGORY_LINE]]);

        $this->assertSame(self::CATEGORY_LINE, $this->help()->describeCategory('Pizza', null, 'en'));

        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains(
            "says \"{$word}\", which nothing you were given says. Write it again without it.",
        ));
    }

    public function test_a_claim_is_fine_once_the_owner_or_the_name_makes_it(): void
    {
        $owned = 'Pizza baked in a wood oven since 2015, to share on easy evenings.';
        $grilled = 'Grilled plates to pass around the table on a long, slow evening.';
        CategoryDescriptionWriter::fake([['text' => $owned], ['text' => $grilled]]);

        $this->assertSame($owned, $this->help()->describeCategory('Pizza', 'pizza from a wood oven since 2015', 'en'));
        // "Grills" says grilled.
        $this->assertSame($grilled, $this->help()->describeCategory('Grills', null, 'en'));
    }

    public function test_the_menu_backs_a_claim_the_restaurants_line_makes(): void
    {
        $line = 'Charcoal chicken and warm bread for slow lunches and easy dinners, shared with the people you love most.';
        RestaurantDescriptionWriter::fake([['text' => $line]]);
        $owner = $this->owner(['name' => ['en' => 'Olive']]);
        $category = Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => 'Mains']]);
        Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $category->id, 'name' => ['en' => 'Charcoal chicken']]);

        $this->assertSame($line, $this->help()->describeRestaurant($owner, null, 'en'));
        RestaurantDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => ! $prompt->contains('Your last answer'));
    }

    public function test_a_word_that_only_looks_like_a_claim_is_not_one(): void
    {
        // "sincere" is not "since", "bestow" not "best".
        $line = 'A sincere welcome for every guest, bestowed with warmth each day.';
        $this->assertSame(65, mb_strlen($line));
        CategoryDescriptionWriter::fake([['text' => $line]]);

        $this->assertSame($line, $this->help()->describeCategory('Welcome', null, 'en'));
    }

    public function test_three_made_up_claims_are_a_failure_not_a_claim(): void
    {
        $oven = 'Thin pizzas baked in our oven, made for long and easy evenings.';
        CategoryDescriptionWriter::fake([['text' => $oven], ['text' => $oven], ['text' => $oven]]);

        $this->expectException(WritingHelpFailed::class);

        $this->help()->describeCategory('Pizza', null, 'en');
    }

    public function test_a_translation_is_never_held_to_the_claims_guard(): void
    {
        // The source is in another language, so its words cannot back
        // anything; the translator only says what the owner said.
        MenuTranslator::fake([['text' => 'Fresh bread from our oven']]);

        $this->assertSame('Fresh bread from our oven', $this->help()->translate('خبز طازج من فرننا', 'ar', 'en'));
    }

    // ---- How long a translation may be -----------------------------------

    public function test_a_translation_is_never_longer_than_its_field(): void
    {
        MenuTranslator::fake([['text' => 'Soupe de lentilles rouges au cumin'], ['text' => 'Soupe aux lentilles']]);

        $this->assertSame('Soupe aux lentilles', $this->help()->translate('Red lentil soup', 'en', 'fr', max: 20));

        // The answer's cap follows the field.
        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Red lentil soup' && $prompt->agent->characters === 20);
        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === "Translate this text again:\nRed lentil soup\n\nYour last answer, \"Soupe de lentilles rouges au cumin\", was 34 characters. Translate it again in at most 20 characters.");
    }

    public function test_with_no_field_given_the_longest_menu_text_is_the_limit(): void
    {
        $long = str_repeat('a', WritingHelp::LONGEST_TEXT);
        MenuTranslator::fake([['text' => $long]]);

        $this->assertSame($long, $this->help()->translate('Soup', 'en', 'fr'));
        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->agent->characters === WritingHelp::LONGEST_TEXT);
    }

    public function test_a_written_description_keeps_its_length_and_loses_its_dashes_in_the_other_language(): void
    {
        // An AI-length category line (65): its translation is held to 60 to 70.
        MenuTranslator::fake([
            ['text' => 'Des soupes.'],
            ['text' => "Des bols de soupe chaude \u{2014} pour bien commencer le repas ensemble"],
        ]);

        $this->assertSame(
            'Des bols de soupe chaude, pour bien commencer le repas ensemble',
            $this->help()->translate(self::CATEGORY_LINE, 'en', 'fr', max: 300, fit: 'category'),
        );
        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Translate it again in 60 to 70 characters.'));
    }

    public function test_the_owners_own_description_of_another_length_is_only_held_to_its_field(): void
    {
        // 22 characters is no AI-written category line, so only the field's
        // limit counts, and its dash stays.
        MenuTranslator::fake([['text' => 'Soupes - faites maison']]);

        $this->assertSame('Soupes - faites maison', $this->help()->translate('Soups - made in house', 'en', 'fr', max: 300, fit: 'category'));
    }

    public function test_a_restaurant_line_keeps_its_length_in_the_other_language(): void
    {
        $answer = str_repeat('b', 105);
        MenuTranslator::fake([['text' => str_repeat('b', 80)], ['text' => $answer]]);

        $this->assertSame($answer, $this->help()->translate(self::RESTAURANT_LINE, 'en', 'fr', max: 2000, fit: 'restaurant'));
        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('in 100 to 110 characters'));
    }

    // ---- Tokens ----------------------------------------------------------

    public function test_the_tokens_every_call_spent_are_counted_and_taken_once(): void
    {
        $answer = fn (string $text, int $in, int $out) => new StructuredTextResponse(['text' => $text], json_encode(['text' => $text]), new TextUsage($in, $out), new Meta('anthropic', 'claude-haiku-5-5'));
        CategoryDescriptionWriter::fake([$answer('Soups.', 300, 5), $answer(self::CATEGORY_LINE, 320, 20)]);

        $this->help()->describeCategory('Soups', null, 'en');

        // The miss counts too: it was paid for.
        $this->assertSame([620, 25], $this->help()->takeTokens());
        $this->assertSame([0, 0], $this->help()->takeTokens());
    }

    // ---- Time ------------------------------------------------------------

    public function test_a_try_waits_at_most_ten_seconds_and_never_more_than_is_left(): void
    {
        $this->freezeTime();
        $tries = 0;
        CategoryDescriptionWriter::fake(function () use (&$tries): array {
            $tries++;
            // 13 seconds go by: 2 are left, just enough for one more try.
            $this->travel(13)->seconds();

            return ['text' => 'Too short.'];
        });

        try {
            $this->help()->describeCategory('Soups', null, 'en');
            $this->fail('Too short every time is a failure.');
        } catch (WritingHelpFailed) {
            // Expected.
        }

        $this->assertSame(2, $tries);
        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->timeout === 10);
        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->timeout === 2);
    }

    public function test_no_try_starts_with_under_two_seconds_left(): void
    {
        $this->freezeTime();
        $tries = 0;
        CategoryDescriptionWriter::fake(function () use (&$tries): array {
            $tries++;
            $this->travel(14)->seconds();

            return ['text' => 'Too short.'];
        });

        $this->expectException(WritingHelpFailed::class);

        try {
            $this->help()->describeCategory('Soups', null, 'en');
        } finally {
            $this->assertSame(1, $tries);
        }
    }

    // ---- Failures --------------------------------------------------------

    /** @return array<string, array{0: \Closure(): \Throwable}> */
    public static function providerFailures(): array
    {
        return [
            'rate limited by the provider' => [fn () => RateLimitedException::forProvider('anthropic')],
            'out of credit' => [fn () => InsufficientCreditsException::forProvider('anthropic')],
            'any other provider error' => [fn () => new AiException('Gemini Error: [400] Bad key')],
            'a refused request' => [fn () => new RequestException(new Response(new Psr7Response(401, [], 'unauthorized')))],
        ];
    }

    #[DataProvider('providerFailures')]
    public function test_a_provider_failure_is_one_failure_and_its_cause_is_logged(\Closure $failure): void
    {
        Exceptions::fake();
        $cause = $failure();
        MenuTranslator::fake(fn () => throw $cause);

        try {
            $this->help()->translate('Soup', 'en', 'fr');
            $this->fail('A provider failure is a WritingHelpFailed.');
        } catch (WritingHelpFailed $failed) {
            $this->assertSame($cause, $failed->getPrevious());
        }

        Exceptions::assertReported($cause::class);
    }

    public function test_a_call_cut_off_on_its_way_counts_as_the_most_it_could_cost(): void
    {
        Exceptions::fake();
        $cause = ProviderConnectionException::forProvider('anthropic');
        MenuTranslator::fake(fn () => throw $cause);
        // The provider's cheapest, Haiku: an answer for a 60-character field is 220 tokens at most.
        $this->help()->takeTokens();

        try {
            $this->help()->translate('Soup', 'en', 'fr', max: 60);
            $this->fail('A cut-off call is a WritingHelpFailed.');
        } catch (WritingHelpFailed $failed) {
            $this->assertSame($cause, $failed->getPrevious());
        }

        // It may have been billed, so the credits' cap never takes it as free.
        $this->assertSame([1500, 220], $this->help()->takeTokens());
        Exceptions::assertReported(ProviderConnectionException::class);
    }

    public function test_a_bug_in_the_app_is_not_dressed_up_as_a_provider_failure(): void
    {
        MenuTranslator::fake(fn () => throw new \LogicException('A bug.'));

        $this->expectException(\LogicException::class);

        $this->help()->translate('Soup', 'en', 'fr');
    }

    public function test_an_answer_of_only_spaces_is_a_failure(): void
    {
        MenuTranslator::fake([['text' => "  \n "]]);

        $this->expectException(WritingHelpFailed::class);

        $this->help()->translate('Soup', 'en', 'fr');
    }

    public function test_the_failure_answers_the_owner_in_one_shape(): void
    {
        $response = (new WritingHelpFailed)->render();

        $this->assertSame(503, $response->getStatusCode());
        $this->assertSame([
            'message' => 'Writing help is not available right now. Try again in a moment.',
            'code' => 'writing_help_failed',
        ], $response->getData(true));
    }

    public function test_the_failure_speaks_arabic_to_an_arabic_dashboard(): void
    {
        app()->setLocale('ar');

        $this->assertSame(
            'المساعدة في الكتابة غير متاحة الآن. حاول مرة أخرى بعد قليل.',
            (new WritingHelpFailed)->render()->getData(true)['message'],
        );
    }
}
