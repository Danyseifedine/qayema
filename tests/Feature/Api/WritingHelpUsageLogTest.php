<?php

namespace Tests\Feature\Api;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Enums\Feature;
use App\Models\Package;
use App\Models\WritingHelpDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Each writing help request in the usage log (PaceWritingHelp writing
 * WritingHelpLog): a use when it gave the owner text, the tokens its calls
 * spent either way, nothing for a request that never reached the provider.
 */
class WritingHelpUsageLogTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const CATEGORY_LINE = 'Warm bowls of soup to start your meal or share across the table.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        config([
            'services.writing_help.provider' => 'anthropic',
            'ai.providers.anthropic.key' => 'test-key',
        ]);
    }

    private function describe($owner)
    {
        return $this->actingAs($owner->user)->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en']);
    }

    public function test_each_text_the_owner_gets_is_a_use_in_the_log(): void
    {
        CategoryDescriptionWriter::fake(fn (): array => ['text' => self::CATEGORY_LINE]);
        $owner = $this->owner();

        $this->describe($owner)->assertOk();
        $this->describe($owner)->assertOk();

        $this->assertSame(2, WritingHelpDay::query()->sole()->uses);
    }

    public function test_the_tokens_gemini_reports_are_logged_the_misses_included(): void
    {
        config(['services.writing_help.provider' => 'gemini', 'ai.providers.gemini.key' => 'gemini-test-key']);
        $answer = fn (string $text): array => [
            'status' => 'completed',
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => json_encode(['text' => $text])]]]],
            'usage' => ['total_input_tokens' => 400, 'total_output_tokens' => 20],
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::sequence()->push($answer('Soups.'))->push($answer(self::CATEGORY_LINE))]);
        $owner = $this->owner();

        $this->describe($owner)->assertOk();

        $day = WritingHelpDay::query()->sole();
        $this->assertSame(1, $day->uses);
        $this->assertSame(800, $day->input_tokens);
        $this->assertSame(40, $day->output_tokens);
    }

    public function test_a_provider_failure_is_no_use_and_the_next_request_counts_only_its_own(): void
    {
        config(['services.writing_help.provider' => 'gemini', 'ai.providers.gemini.key' => 'gemini-test-key']);
        $answer = [
            'status' => 'completed',
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => json_encode(['text' => 'Too short.'])]]]],
            'usage' => ['total_input_tokens' => 100, 'total_output_tokens' => 5],
        ];
        Http::fake(['generativelanguage.googleapis.com/*' => Http::response($answer)]);
        $owner = $this->owner();

        // Three misses: a failure, its three calls paid for.
        $this->describe($owner)->assertStatus(503);
        $day = WritingHelpDay::query()->sole();
        $this->assertSame(0, $day->uses);
        $this->assertSame(300, $day->input_tokens);

        // A second request is not charged the first one's tokens again.
        $this->describe($owner)->assertStatus(503);
        $this->assertSame(600, $day->fresh()->input_tokens);
    }

    public function test_a_failure_before_any_answer_leaves_no_trace(): void
    {
        MenuTranslator::fake(fn () => throw ProviderOverloadedException::forProvider('anthropic'));
        $owner = $this->owner(['second_locale' => 'fr']);

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.translate'), ['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])
            ->assertStatus(503);

        $this->assertSame(0, WritingHelpDay::query()->count());
    }

    public function test_a_refused_request_a_busy_one_and_a_spent_day_leave_no_trace(): void
    {
        CategoryDescriptionWriter::fake(fn (): array => ['text' => self::CATEGORY_LINE]);
        $owner = $this->owner();

        $this->actingAs($owner->user)->postJson(route('api.writing.category-description'), ['language' => 'en'])->assertUnprocessable();

        $lock = Cache::lock("writing-help:restaurant:{$owner->id}", 30);
        $lock->get();
        $this->describe($owner)->assertStatus(503);
        $lock->release();

        Package::default()->update(['ai_budget_cents' => 0]);
        $this->describe($owner->fresh())->assertNotFound();

        $this->assertSame(0, WritingHelpDay::query()->count());
    }
}
