<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Testing\TestResponse;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The writing help through the real Gemini driver, with only the HTTP faked:
 * what is sent to Google, and every way an answer can come back.
 */
class WritingHelpGeminiTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const INTERACTIONS = 'https://generativelanguage.googleapis.com/v1beta/interactions';

    private const CATEGORY_LINE = 'Warm bowls of soup to start your meal or share across the table.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $this->defaultPackageSets(Feature::WritingHelpLimit, 5);
        config([
            'services.writing_help.provider' => 'gemini',
            'services.writing_help.model' => null,
            'ai.providers.gemini.key' => 'gemini-test-key',
        ]);
    }

    /** Gemini's answer to an interaction, carrying the structured `text`. */
    private static function answer(string $text): array
    {
        return [
            'status' => 'completed',
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => json_encode(['text' => $text])]]]],
            'usage' => ['total_input_tokens' => 120, 'total_output_tokens' => 12],
        ];
    }

    private function translate(): TestResponse
    {
        return $this->postJson(route('api.writing.translate'), ['text' => 'Lentil soup', 'from' => 'en', 'to' => 'ar']);
    }

    private function leftToday($owner): ?int
    {
        return $this->actingAs($owner->user)->getJson(route('api.user'))->json('data.restaurant.writing_help.left');
    }

    public function test_a_translation_is_one_interaction_with_the_key_the_cheapest_model_and_the_text(): void
    {
        Http::fake([self::INTERACTIONS => Http::response(self::answer('شوربة عدس'))]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)->translate()
            ->assertOk()
            ->assertExactJson(['data' => ['text' => 'شوربة عدس', 'left' => 4]]);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->url() === self::INTERACTIONS
            && $request->hasHeader('x-goog-api-key', 'gemini-test-key')
            && $request['model'] === 'gemini-3.1-flash-lite'
            && str_contains((string) $request['system_instruction'], 'from English into العربية')
            && str_contains(json_encode($request['input']), 'Lentil soup'));
    }

    public function test_a_model_set_in_the_env_is_the_one_asked(): void
    {
        config(['services.writing_help.model' => 'gemini-3.5-flash-lite']);
        Http::fake([self::INTERACTIONS => Http::response(self::answer('شوربة عدس'))]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)->translate()->assertOk();

        Http::assertSent(fn (Request $request): bool => $request['model'] === 'gemini-3.5-flash-lite');
    }

    public function test_a_description_of_the_wrong_length_is_asked_again_over_the_wire(): void
    {
        Http::fake([self::INTERACTIONS => Http::sequence()
            ->push(self::answer('Soups.'))
            ->push(self::answer(self::CATEGORY_LINE)),
        ]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', self::CATEGORY_LINE);

        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => str_contains(json_encode($request['input'], JSON_UNESCAPED_UNICODE), 'was 6 characters'));
    }

    /** @return array<string, array{0: \Closure(): mixed}> */
    public static function geminiFailures(): array
    {
        return [
            'a server error' => [fn () => Http::response(['error' => ['code' => 500, 'message' => 'Internal']], 500)],
            'rate limited' => [fn () => Http::response(['error' => ['code' => 429, 'message' => 'Quota']], 429)],
            'overloaded' => [fn () => Http::response(['error' => ['code' => 503, 'message' => 'Overloaded']], 503)],
            'a refused key' => [fn () => Http::response(['error' => ['code' => 403, 'message' => 'API key not valid']], 403)],
            'an error inside a 200' => [fn () => Http::response(['error' => ['code' => 400, 'message' => 'Bad request']])],
            'an interaction that failed' => [fn () => Http::response(['status' => 'failed', 'errors' => [['code' => 'internal', 'message' => 'Failed']]])],
            'an empty answer' => [fn () => Http::response([])],
            'no connection' => [fn () => Http::failedConnection()],
        ];
    }

    /** @param  \Closure(): mixed  $failure */
    #[\PHPUnit\Framework\Attributes\DataProvider('geminiFailures')]
    public function test_every_way_gemini_fails_is_try_again_and_costs_no_use(\Closure $failure): void
    {
        Http::fake([self::INTERACTIONS => $failure()]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)->translate()
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Writing help is not available right now. Try again in a moment.',
                'code' => 'writing_help_failed',
            ]);

        $this->assertSame(5, $this->leftToday($owner));
    }

    public function test_an_answer_that_is_not_the_asked_shape_is_try_again(): void
    {
        Http::fake([self::INTERACTIONS => Http::response([
            'status' => 'completed',
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => 'Not JSON at all']]]],
        ])]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)->translate()
            ->assertStatus(503)
            ->assertJsonPath('code', 'writing_help_failed');

        $this->assertSame(5, $this->leftToday($owner));
    }

    public function test_no_request_leaves_the_server_while_the_writing_help_is_off(): void
    {
        Http::fake();
        $this->defaultPackageSets(Feature::WritingHelpLimit, 0);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)->translate()->assertNotFound();

        Http::assertNothingSent();
    }
}
