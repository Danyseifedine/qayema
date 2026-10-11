<?php

namespace Tests\Integration\Ai;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Services\Ai\WritingHelp;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Temperature;
use Laravel\Ai\Attributes\UseCheapestModel;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use Tests\TestCase;

/**
 * The writing help's three agents: what they are told, the one `text` they
 * answer with, and the model they run on.
 */
class WritingHelpAgentsTest extends TestCase
{
    /** @return array<string, array{0: Agent&HasStructuredOutput}> */
    public static function agents(): array
    {
        return [
            'the translator' => [new MenuTranslator('English', 'العربية')],
            'the category writer' => [new CategoryDescriptionWriter('English', 60, 70)],
            'the restaurant writer' => [new RestaurantDescriptionWriter('Français', 100, 110)],
        ];
    }

    #[DataProvider('agents')]
    public function test_each_answers_with_one_required_text(Agent&HasStructuredOutput $agent): void
    {
        $schema = $agent->schema(new JsonSchemaTypeFactory);

        $this->assertSame(['text'], array_keys($schema));
        $this->assertSame('string', $schema['text']->toArray()['type']);
        $this->assertTrue((fn () => $this->required)->call($schema['text']));
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

    #[DataProvider('agents')]
    public function test_one_call_waits_at_most_the_writing_helps_seconds(Agent $agent): void
    {
        $this->assertSame(WritingHelp::TRY_SECONDS, $agent->timeout());
        $this->assertSame(10, WritingHelp::TRY_SECONDS);
        $this->assertLessThan(WritingHelp::TIME_BUDGET, WritingHelp::TRY_SECONDS);
    }

    public function test_the_translator_is_told_both_languages_and_to_add_nothing(): void
    {
        $instructions = (string) (new MenuTranslator('English', 'العربية'))->instructions();

        $this->assertStringContainsString('from English into العربية', $instructions);
        $this->assertStringContainsString('Add nothing, explain nothing, and leave nothing out.', $instructions);
        $this->assertStringContainsString('Answer with the translation only.', $instructions);
    }

    public function test_the_translator_stays_close_to_the_text(): void
    {
        $this->assertSame(0.2, $this->attribute(MenuTranslator::class, Temperature::class));
        $this->assertSame(2000, $this->attribute(MenuTranslator::class, MaxTokens::class));
    }

    public function test_the_category_writer_is_told_its_language_length_and_what_not_to_invent(): void
    {
        $instructions = (string) (new CategoryDescriptionWriter('العربية', 60, 70))->instructions();

        $this->assertStringContainsString("on a restaurant's menu, in العربية.", $instructions);
        $this->assertStringContainsString('60 to 70 characters', $instructions);
        $this->assertStringContainsString('No dashes or hyphens of any kind', $instructions);
        $this->assertStringContainsString('wood fire', $instructions);
        $this->assertStringContainsString('Those words are fine only when the owner wrote them.', $instructions);
        $this->assertStringContainsString('every fact in it must be in your answer', $instructions);
        $this->assertStringContainsString('never copy them word for word', $instructions);
    }

    public function test_the_restaurant_writer_uses_the_menu_only_as_inspiration(): void
    {
        $instructions = (string) (new RestaurantDescriptionWriter('English', 100, 110))->instructions();

        $this->assertStringContainsString('100 to 110 characters', $instructions);
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
