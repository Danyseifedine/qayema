<?php

namespace Tests\Feature\Api;

use App\Ai\Agents\MenuListTranslator;
use App\Ai\Agents\MenuTranslator;
use App\Enums\Feature;
use App\Services\Ai\WritingHelp;
use App\Support\MenuTextLimits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Prompts\AgentPrompt;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Everything a dish's save leaves in one language, translated in one go
 * (POST /api/writing/translate-many): in the order sent, one call per
 * direction, one use of the day, and held to each field's limit.
 */
class WritingHelpListTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        config([
            'services.writing_help.provider' => 'anthropic',
            'services.writing_help.at_once' => 10,
            'ai.providers.anthropic.key' => 'test-key',
        ]);
    }

    /** @param  list<array<string, mixed>>  $items */
    private function translateMany(array $items): TestResponse
    {
        return $this->postJson(route('api.writing.translate-many'), ['items' => $items]);
    }

    public function test_a_list_is_translated_in_the_order_sent_for_one_use(): void
    {
        MenuListTranslator::fake([
            ['texts' => [' كباب ', 'كبير', 'صغير']],
            ['texts' => ['Lamb, rice']],
        ]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translateMany([
                ['text' => 'Kebab', 'from' => 'en', 'to' => 'ar', 'max' => MenuTextLimits::DISH_NAME],
                ['text' => 'لحم، أرز', 'from' => 'ar', 'to' => 'en', 'max' => MenuTextLimits::INGREDIENTS],
                ['text' => 'Large', 'from' => 'en', 'to' => 'ar', 'max' => MenuTextLimits::CHOICE_NAME],
                ['text' => 'Small', 'from' => 'en', 'to' => 'ar'],
            ])
            ->assertOk()
            ->assertExactJson(['data' => ['texts' => ['كباب', 'Lamb, rice', 'كبير', 'صغير'], 'credits_left' => 499]]);

        // One call each way, the texts as a JSON list, its cap their limits together.
        MenuListTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === '["Kebab","Large","Small"]'
            && $prompt->timeout === WritingHelp::LIST_TRY_SECONDS
            && $prompt->agent->characters === MenuTextLimits::DISH_NAME + MenuTextLimits::CHOICE_NAME + WritingHelp::LONGEST_TEXT
            && $prompt->agent->pieces === 3
            && $prompt->agent->from === 'English'
            && $prompt->agent->to === 'العربية');
        MenuListTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === '["لحم، أرز"]'
            && $prompt->agent->from === 'العربية');
        MenuTranslator::assertNeverPrompted();
    }

    public function test_a_list_short_of_answers_is_asked_again(): void
    {
        MenuListTranslator::fake([
            ['texts' => ['كباب']],
            ['texts' => ['كباب', 'كبير']],
        ]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translateMany([
                ['text' => 'Kebab', 'from' => 'en', 'to' => 'ar'],
                ['text' => 'Large', 'from' => 'en', 'to' => 'ar'],
            ])
            ->assertOk()
            ->assertJsonPath('data.texts', ['كباب', 'كبير']);

        MenuListTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Your last answer had 1 translations for 2 texts. Answer with exactly 2'));
    }

    public function test_a_translation_past_its_fields_limit_or_empty_is_asked_again(): void
    {
        MenuListTranslator::fake([
            ['texts' => ['حجم كبير جدا جدا', 'صغير']],
            ['texts' => ['كبير', '  ']],
            ['texts' => ['كبير', 'صغير']],
        ]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translateMany([
                ['text' => 'Large', 'from' => 'en', 'to' => 'ar', 'max' => 8],
                ['text' => 'Small', 'from' => 'en', 'to' => 'ar', 'max' => 8],
            ])
            ->assertOk()
            ->assertJsonPath('data.texts', ['كبير', 'صغير']);

        MenuListTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Translation 1 of your last answer, "حجم كبير جدا جدا", was 16 characters. Keep it to at most 8.'));
        MenuListTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Translation 2 of your last answer was empty.'));
    }

    public function test_never_a_list_that_breaks_the_rules_and_the_use_is_given_back(): void
    {
        MenuListTranslator::fake(fn (): array => ['texts' => []]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translateMany([['text' => 'Kebab', 'from' => 'en', 'to' => 'ar']])
            ->assertStatus(503)
            ->assertJsonPath('code', 'writing_help_failed');
        // Nothing was given, so no credit was taken.
        $this->actingAs($owner->user)->getJson('/api/user')->assertJsonPath('data.restaurant.writing_help.credits.left', 500);
    }

    public function test_a_provider_that_fails_is_one_clear_answer_to_try_again(): void
    {
        MenuListTranslator::fake(fn () => throw ProviderOverloadedException::forProvider('anthropic'));
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translateMany([['text' => 'Kebab', 'from' => 'en', 'to' => 'ar']])
            ->assertStatus(503)
            ->assertJsonPath('code', 'writing_help_failed');
    }

    /** @return array<string, array{0: mixed, 1: string}> */
    public static function refused(): array
    {
        $item = ['text' => 'Kebab', 'from' => 'en', 'to' => 'ar'];

        return [
            'no list' => [null, 'items'],
            'an empty list' => [[], 'items'],
            'too many pieces' => [array_fill(0, WritingHelp::LIST_ITEMS + 1, $item), 'items'],
            'too much text together' => [array_fill(0, intdiv(WritingHelp::LIST_LENGTH, MenuTextLimits::LONGEST) + 1, [...$item, 'text' => str_repeat('a', MenuTextLimits::LONGEST)]), 'items'],
            'limits past what a dish holds together' => [array_fill(0, intdiv(WritingHelp::LIST_LENGTH, MenuTextLimits::LONGEST) + 1, [...$item, 'max' => MenuTextLimits::LONGEST]), 'items'],
            'a piece too long' => [[[...$item, 'text' => str_repeat('a', MenuTextLimits::LONGEST + 1)]], 'items.0.text'],
            'an empty piece' => [[[...$item, 'text' => '']], 'items.0.text'],
            'a language off the menu' => [[[...$item, 'to' => 'fr']], 'items.0.to'],
            'into the same language' => [[$item, [...$item, 'to' => 'en']], 'items.1.to'],
            'a limit past the longest' => [[[...$item, 'max' => MenuTextLimits::LONGEST + 1]], 'items.0.max'],
            'something else in a piece' => [[[...$item, 'fit' => 'category']], 'items.0'],
        ];
    }

    #[DataProvider('refused')]
    public function test_what_a_list_may_carry(mixed $items, string $error): void
    {
        MenuListTranslator::fake();
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.translate-many'), ['items' => $items])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([$error]);
        MenuListTranslator::assertNeverPrompted();
    }

    public function test_the_longest_list_goes_through(): void
    {
        MenuListTranslator::fake(fn (): array => ['texts' => array_fill(0, WritingHelp::LIST_ITEMS, 'كباب')]);
        $owner = $this->owner(['second_locale' => 'ar']);
        $length = intdiv(WritingHelp::LIST_LENGTH, WritingHelp::LIST_ITEMS);
        $text = str_repeat('a', $length);

        $this->actingAs($owner->user)
            ->translateMany(array_fill(0, WritingHelp::LIST_ITEMS, ['text' => $text, 'from' => 'en', 'to' => 'ar', 'max' => $length]))
            ->assertOk()
            ->assertJsonCount(WritingHelp::LIST_ITEMS, 'data.texts');
    }

    public function test_only_with_writing_help_and_a_signed_in_owner(): void
    {
        $item = ['text' => 'Kebab', 'from' => 'en', 'to' => 'ar'];
        $this->translateMany([$item])->assertUnauthorized();

        config(['ai.providers.anthropic.key' => null]);
        $owner = $this->owner(['second_locale' => 'ar']);
        $this->actingAs($owner->user)->translateMany([$item])->assertNotFound();
    }
}
