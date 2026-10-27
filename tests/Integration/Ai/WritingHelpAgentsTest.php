<?php

namespace Tests\Integration\Ai;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuListTranslator;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpSetup;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * The writing help's agents: what they are told, what they answer with,
 * the model they run on and the most an answer may cost.
 */
class WritingHelpAgentsTest extends TestCase
{
    /** @return array<string, array{0: Agent&HasStructuredOutput}> */
    public static function agents(): array
    {
        return [
            'the translator' => [new MenuTranslator('English', 'العربية', 60)],
            'the list translator' => [new MenuListTranslator('English', 'العربية', 400, 3)],
            'the category writer' => [new CategoryDescriptionWriter('English', 60, 70)],
            'the restaurant writer' => [new RestaurantDescriptionWriter('Français', 100, 110)],
        ];
    }

    #[DataProvider('agents')]
    public function test_each_answers_with_one_required_text_or_a_writers_versions(Agent&HasStructuredOutput $agent): void
    {
        $schema = $agent->schema(new JsonSchemaTypeFactory);
        // A description writer answers with several versions to pick from.
        $key = $agent instanceof MenuTranslator && ! $agent instanceof MenuListTranslator ? 'text' : 'texts';

        $this->assertSame([$key], array_keys($schema));
        $this->assertSame($key === 'texts' ? 'array' : 'string', $schema[$key]->toArray()['type']);
        $this->assertTrue((fn () => $this->required)->call($schema[$key]));
    }

    #[DataProvider('agents')]
    public function test_each_runs_on_the_configured_provider_and_model_or_the_cheapest(Agent $agent): void
    {
        config(['services.writing_help.provider' => 'gemini', 'services.writing_help.model' => null]);

        $this->assertSame('gemini', $agent->provider());
        $this->assertNull($agent->model());
        $this->assertNotEmpty((new ReflectionClass($agent))->getAttributes(UseCheapestModel::class));

        config(['services.writing_help.model' => 'gemini-3.5-flash-lite']);
        $this->assertSame('gemini-3.5-flash-lite', $agent->model());
    }

    public function test_one_call_waits_at_most_the_writing_helps_seconds(): void
    {
        $this->assertSame(10, WritingHelp::TRY_SECONDS);
        $this->assertLessThan(WritingHelp::TIME_BUDGET, WritingHelp::TRY_SECONDS);
        $this->assertLessThan(WritingHelp::LIST_TIME_BUDGET, WritingHelp::LIST_TRY_SECONDS);
    }

    #[DataProvider('agents')]
    public function test_claude_thinks_at_low_effort_and_other_providers_get_nothing_extra(Agent $agent): void
    {
        $low = ['output_config' => ['effort' => 'low']];
        $this->assertInstanceOf(HasProviderOptions::class, $agent);
        // A Claude model other than Haiku thinks at low effort.
        config(['services.writing_help.provider' => 'anthropic']);
        foreach (['claude-sonnet-5-5', 'claude-opus-5-5'] as $model) {
            config(['services.writing_help.model' => $model]);
            $this->assertSame($low, $agent->providerOptions(Lab::Anthropic));
            $this->assertSame($low, $agent->providerOptions('anthropic'));
        }
        $this->assertSame([], $agent->providerOptions(Lab::Gemini));
    }

    /**
     * What one call may cost at most: an answer as long as its field (or
     * every version together), two tokens a character and the JSON around
     * it, and room to think for any model but Haiku.
     */
    public function test_each_answer_is_capped_to_what_its_text_needs(): void
    {
        $agents = fn (): array => [
            (new MenuTranslator('English', 'العربية', 60))->maxTokens(),
            (new MenuTranslator('English', 'العربية', 300))->maxTokens(),
            // Three texts of 400 characters together, each a little JSON more.
            (new MenuListTranslator('English', 'العربية', 400, 3))->maxTokens(),
            // Seven versions from 45 to 75 characters, 420 together.
            (new CategoryDescriptionWriter('English', 60, 70))->maxTokens(),
            // From 85 to 115, 700 together.
            (new RestaurantDescriptionWriter('English', 100, 110))->maxTokens(),
        ];

        config(['services.writing_help.provider' => 'anthropic', 'services.writing_help.model' => 'claude-haiku-5-5']);
        $this->assertSame([220, 700, 915, 940, 1500], $agents());

        config(['services.writing_help.model' => 'claude-sonnet-5-5']);
        $this->assertSame([1220, 1700, 1915, 1940, 2500], $agents());

        // Another provider's model may think by default: room for it too.
        config(['services.writing_help.provider' => 'gemini', 'services.writing_help.model' => 'gemini-3.5-flash-lite']);
        $this->assertSame([1220, 1700, 1915, 1940, 2500], $agents());
    }

    #[DataProvider('agents')]
    public function test_on_haiku_nothing_thinks_set_or_picked_as_the_cheapest(Agent $agent): void
    {
        config(['services.writing_help.provider' => 'anthropic', 'services.writing_help.model' => 'claude-haiku-5-5']);
        $this->assertSame(['thinking' => ['type' => 'disabled']], $agent->providerOptions(Lab::Anthropic));

        // AI_MODEL empty: the provider's cheapest, Haiku, all the same.
        config(['services.writing_help.model' => null]);
        $this->assertSame('claude-haiku-5-5', WritingHelpSetup::effectiveModel());
        $this->assertSame(['thinking' => ['type' => 'disabled']], $agent->providerOptions(Lab::Anthropic));
    }

    public function test_a_description_is_asked_for_as_versions_spread_around_its_length(): void
    {
        $category = new CategoryDescriptionWriter('English', 60, 70);
        $this->assertSame([45, 50, 55, 60, 65, 70, 75], $category->versionLengths());
        $this->assertStringContainsString('Give 7 versions of it', (string) $category->instructions());
        $this->assertStringContainsString('about 45 characters, then 50, 55, 60, 65, 70 and 75', (string) $category->instructions());

        $restaurant = new RestaurantDescriptionWriter('English', 100, 110);
        $this->assertSame([85, 90, 95, 100, 105, 110, 115], $restaurant->versionLengths());
        $this->assertStringContainsString('about 85 characters, then 90, 95, 100, 105, 110 and 115', (string) $restaurant->instructions());
    }

    public function test_the_translator_is_told_both_languages_and_to_add_nothing(): void
    {
        $instructions = (string) (new MenuTranslator('English', 'العربية', 60))->instructions();

        $this->assertStringContainsString('from English into العربية', $instructions);
        $this->assertStringContainsString('Add nothing, explain nothing, and leave nothing out.', $instructions);
        $this->assertStringContainsString('Answer with the translation only.', $instructions);
    }

    public function test_the_translator_stays_close_to_the_text(): void
    {
        $this->assertSame(0.2, $this->attribute(MenuTranslator::class, Temperature::class));
    }

    public function test_the_category_writer_is_told_its_language_length_and_what_not_to_invent(): void
    {
        $instructions = (string) (new CategoryDescriptionWriter('العربية', 60, 70))->instructions();

        $this->assertStringContainsString("on a restaurant's menu, in العربية.", $instructions);
        $this->assertStringContainsString('about 45 characters, then 50, 55, 60, 65, 70 and 75', $instructions);
        $this->assertStringContainsString('No dashes or hyphens of any kind', $instructions);
        $this->assertStringContainsString('wood fire', $instructions);
        $this->assertStringContainsString('Those words are fine only when the owner wrote them.', $instructions);
        $this->assertStringContainsString('every fact in it must be in your answer', $instructions);
        $this->assertStringContainsString('never copy them word for word', $instructions);
    }

    public function test_the_restaurant_writer_uses_the_menu_only_as_inspiration(): void
    {
        $instructions = (string) (new RestaurantDescriptionWriter('English', 100, 110))->instructions();

        $this->assertStringContainsString('about 85 characters, then 90, 95, 100, 105, 110 and 115', $instructions);
        $this->assertStringContainsString("Never list or name the menu's categories or dishes.", $instructions);
        $this->assertStringContainsString('When the menu is empty, write a warm welcome that would suit any restaurant', $instructions);
        $this->assertStringContainsString('No dashes or hyphens of any kind', $instructions);
        $this->assertStringContainsString('no history, years, family, chefs', $instructions);
    }

    /** @return array<string, array{0: string, 1: int, 2: int}> */
    public static function examples(): array
    {
        return [
            'the category example' => [CategoryDescriptionWriter::class, 60, 70],
            'the restaurant examples' => [RestaurantDescriptionWriter::class, 100, 110],
        ];
    }

    /**
     * The good examples the writers are shown must keep the rules they
     * teach: the stated length, true, and no dash.
     */
    #[DataProvider('examples')]
    public function test_every_good_example_keeps_the_rules_it_teaches(string $writer, int $shortest, int $longest): void
    {
        $instructions = (string) (new $writer('English', $shortest, $longest))->instructions();
        preg_match_all('/^\s*Good, [^(]*\((\d+) characters\): (.+)$/m', $instructions, $examples, PREG_SET_ORDER);

        $this->assertNotEmpty($examples);
        foreach ($examples as [, $stated, $example]) {
            $this->assertSame((int) $stated, mb_strlen($example), "\"{$example}\" is not the length it claims.");
            $this->assertGreaterThanOrEqual($shortest, mb_strlen($example));
            $this->assertLessThanOrEqual($longest, mb_strlen($example));
            $this->assertSame($example, WritingHelp::withoutDashes($example), "\"{$example}\" has a dash.");
        }
    }

    public function test_the_writers_are_steadier_than_creative(): void
    {
        $this->assertSame(0.4, $this->attribute(CategoryDescriptionWriter::class, Temperature::class));
        $this->assertSame(0.4, $this->attribute(RestaurantDescriptionWriter::class, Temperature::class));
    }

    private function attribute(string $class, string $attribute): mixed
    {
        return (new ReflectionClass($class))->getAttributes($attribute)[0]->newInstance()->value;
    }
}
