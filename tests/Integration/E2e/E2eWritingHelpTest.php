<?php

namespace Tests\Integration\E2e;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpCredits;
use App\Services\Ai\WritingHelpSetup;
use App\Services\Ai\WritingHelpUses;
use Illuminate\Cache\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\E2e\E2eController;
use Tests\E2e\E2eServiceProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The e2e server's pretend Gemini (E2eServiceProvider): the writing help on
 * only for a request that asks for it, and answers the real driver reads,
 * so the Playwright suite runs the whole path without leaving the machine.
 */
class E2eWritingHelpTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function boot(bool $asking): void
    {
        $request = Request::create('/api/user');
        if ($asking) {
            $request->headers->set(E2eServiceProvider::WRITING_HELP_HEADER, '1');
        }
        $this->app->instance('request', $request);

        $this->app->register(E2eServiceProvider::class, force: true);
    }

    public function test_it_is_off_for_a_request_that_does_not_ask_even_with_a_real_key_in_the_env(): void
    {
        config(['services.writing_help.provider' => 'anthropic', 'ai.providers.anthropic.key' => 'a-real-key']);

        $this->boot(asking: false);

        $this->assertFalse(WritingHelpSetup::isOn());
    }

    public function test_it_is_on_with_gemini_and_a_pretend_key_for_one_that_asks(): void
    {
        $this->boot(asking: true);

        $this->assertTrue(WritingHelpSetup::isOn());
        $this->assertSame('gemini', WritingHelpSetup::provider());
        $this->assertNull(WritingHelpSetup::model());
        $this->assertSame('e2e-not-a-key', config('ai.providers.gemini.key'));
    }

    public function test_its_fixed_lines_are_the_lengths_the_writing_help_holds_them_to(): void
    {
        [$shortest, $longest] = WritingHelp::CATEGORY_LENGTH;
        $this->assertGreaterThanOrEqual($shortest, mb_strlen(E2eServiceProvider::CATEGORY_LINE));
        $this->assertLessThanOrEqual($longest, mb_strlen(E2eServiceProvider::CATEGORY_LINE));

        [$shortest, $longest] = WritingHelp::RESTAURANT_LENGTH;
        $this->assertGreaterThanOrEqual($shortest, mb_strlen(E2eServiceProvider::RESTAURANT_LINE));
        $this->assertLessThanOrEqual($longest, mb_strlen(E2eServiceProvider::RESTAURANT_LINE));

        foreach ([E2eServiceProvider::CATEGORY_LINE, E2eServiceProvider::RESTAURANT_LINE] as $line) {
            $this->assertSame($line, WritingHelp::withoutDashes($line));
        }
    }

    public function test_it_answers_each_kind_of_ask_in_geminis_shape(): void
    {
        $json = fn (array $answer): array => json_decode($answer['steps'][0]['content'][0]['text'], true);
        $text = fn (array $answer): string => $json($answer)['text'];

        // A description writer's answer is its versions.
        $this->assertSame([E2eServiceProvider::CATEGORY_LINE], $json(E2eServiceProvider::geminiAnswer([
            'system_instruction' => "You write the short line printed under a category's title on a restaurant's menu",
        ]))['texts']);
        $this->assertSame([E2eServiceProvider::RESTAURANT_LINE], $json(E2eServiceProvider::geminiAnswer([
            'system_instruction' => 'You write the short line a restaurant introduces itself with',
        ]))['texts']);
        $this->assertSame('Translated: Lentil soup', $text(E2eServiceProvider::geminiAnswer([
            'system_instruction' => 'You translate a restaurant\'s menu text',
            'input' => [['type' => 'user_input', 'content' => [['type' => 'text', 'text' => 'Lentil soup']]]],
        ])));

        $answer = E2eServiceProvider::geminiAnswer([]);
        $this->assertSame('completed', $answer['status']);
        $this->assertSame('model_output', $answer['steps'][0]['type']);
    }

    public function test_the_whole_path_runs_on_it_and_nothing_leaves_the_machine(): void
    {
        $this->boot(asking: true);
        Http::preventStrayRequests();
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.translate'), ['text' => 'Lentil soup', 'from' => 'en', 'to' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Translated: Lentil soup');
        $this->actingAs($owner->user)
            ->postJson(route('api.writing.translate-many'), ['items' => [
                ['text' => 'Lentil soup', 'from' => 'en', 'to' => 'ar'],
                ['text' => 'Large', 'from' => 'en', 'to' => 'ar', 'max' => 60],
            ]])
            ->assertOk()
            ->assertJsonPath('data.texts', ['Translated: Lentil soup', 'Translated: Large']);
        $this->actingAs($owner->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', E2eServiceProvider::CATEGORY_LINE);
        $this->actingAs($owner->user)
            ->postJson(route('api.writing.restaurant-description'), ['language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', E2eServiceProvider::RESTAURANT_LINE);
    }

    public function test_its_requests_count_in_a_cache_that_outlives_the_request(): void
    {
        $this->boot(asking: true);

        $this->assertSame(E2eServiceProvider::WRITING_HELP_CACHE, config('cache.default'));
        $this->assertSame('file', config('cache.stores.'.E2eServiceProvider::WRITING_HELP_CACHE.'.driver'));
        $this->assertSame(E2eServiceProvider::root('cache'), config('cache.stores.'.E2eServiceProvider::WRITING_HELP_CACHE.'.path'));
    }

    public function test_a_request_that_does_not_ask_keeps_the_suites_own_cache(): void
    {
        $default = config('cache.default');

        $this->boot(asking: false);

        $this->assertSame($default, config('cache.default'));
    }

    public function test_a_scenario_can_start_with_uses_already_spent_today(): void
    {
        $this->boot(asking: false);
        $store = Cache::store(E2eServiceProvider::WRITING_HELP_CACHE);

        try {
            $response = app(E2eController::class)->scenario(Request::create('/__e2e/scenario', 'POST', ['package' => 'pro', 'template' => null, 'writing_help_used' => 3]));
            $restaurant = Restaurant::query()->findOrFail($response->getData(true)['restaurant']['id']);

            $uses = new WritingHelpUses(new RateLimiter($store));
            $this->assertSame(100, $uses->limit($restaurant));
            $this->assertSame(97, $uses->left($restaurant));
        } finally {
            $store->flush();
        }
    }

    public function test_a_scenario_can_start_with_credits_already_spent_this_month(): void
    {
        $this->boot(asking: false);

        $response = app(E2eController::class)->scenario(Request::create('/__e2e/scenario', 'POST', [
            'package' => 'pro', 'template' => null, 'writing_help_days' => [['uses' => 4, 'credits' => 7400, 'cost' => '0.0100']],
        ]));
        $restaurant = Restaurant::query()->findOrFail($response->getData(true)['restaurant']['id']);

        $this->assertSame(100, app(WritingHelpCredits::class)->left($restaurant));
    }
}
