<?php

namespace Tests\Feature\Api;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Enums\Feature;
use App\Models\AppSetting;
use App\Models\Package;
use App\Models\Restaurant;
use App\Models\WritingHelpDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The AI buttons' credits a month: each action takes its credits when the
 * owner gets the text, none are taken for a failure, used up is a 429 that
 * says when they come back, the real cost never passes what the credits are
 * worth, and the owner is never told money or the model.
 */
class WritingHelpCreditsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->travelTo(Carbon::parse('2026-10-20 12:00', 'UTC'));
        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        config([
            'services.writing_help.provider' => 'anthropic',
            'services.writing_help.model' => 'claude-haiku-5-5',
            'services.writing_help.at_once' => 10,
            'ai.providers.anthropic.key' => 'test-key',
        ]);
    }

    private function freeOwner(array $restaurant = []): Restaurant
    {
        return $this->ownerOn('free', ['second_locale' => 'ar', 'timezone' => 'UTC', ...$restaurant]);
    }

    private function translate(): TestResponse
    {
        return $this->postJson(route('api.writing.translate'), ['text' => 'Soup', 'from' => 'en', 'to' => 'ar']);
    }

    private function describe(): TestResponse
    {
        return $this->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en']);
    }

    public function test_a_translation_takes_one_credit_a_description_three_and_each_answer_says_whats_left(): void
    {
        MenuTranslator::fake([['text' => 'شوربة']]);
        CategoryDescriptionWriter::fake([['texts' => [str_repeat('a', 65)]]]);
        $owner = $this->freeOwner();

        $this->actingAs($owner->user)->translate()->assertOk()->assertJsonPath('data.credits_left', 499);
        $this->actingAs($owner->user)->describe()->assertOk()->assertJsonPath('data.credits_left', 496);

        $this->actingAs($owner->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.writing_help.credits', [
                'limit' => 500,
                'left' => 496,
                'resets_on' => '2026-11-01',
                'prices' => ['translation' => 1, 'description' => 3],
            ]);
        $day = WritingHelpDay::query()->sole();
        $this->assertSame([4, 1, 1], [$day->credits, $day->translation_uses, $day->description_uses]);
    }

    public function test_the_prices_are_the_admins(): void
    {
        AppSetting::write(['ai_credits_translation' => 4]);
        MenuTranslator::fake([['text' => 'شوربة']]);

        $this->actingAs($this->freeOwner()->user)->translate()->assertOk()->assertJsonPath('data.credits_left', 496);
    }

    public function test_a_failure_takes_no_credits(): void
    {
        MenuTranslator::fake(fn () => throw new ProviderOverloadedException('busy'));
        $owner = $this->freeOwner();

        $this->actingAs($owner->user)->translate()->assertStatus(503);

        $this->assertSame(500, $this->actingAs($owner->user)->getJson(route('api.user'))->json('data.restaurant.writing_help.credits.left'));
    }

    public function test_used_up_says_when_they_come_back_and_never_asks_the_ai(): void
    {
        MenuTranslator::fake()->preventStrayPrompts();
        $owner = $this->freeOwner();
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 500]);

        $this->actingAs($owner->user)->translate()
            ->assertStatus(429)
            ->assertHeader('Retry-After', (string) (12 * 86400 - 12 * 3600))
            ->assertExactJson([
                'message' => 'You have used this month\'s AI credits. They come back on 1 November.',
                'code' => 'writing_help_credits',
                'retry_after' => 12 * 86400 - 12 * 3600,
                'resets_on' => '2026-11-01',
                'credits_left' => 0,
            ]);

        $this->actingAs($owner->user)->postJson(route('api.writing.translate'), ['text' => 'Soup', 'from' => 'en', 'to' => 'ar'], ['Accept-Language' => 'ar'])
            ->assertJsonPath('message', 'استخدمت أرصدة الذكاء الاصطناعي لهذا الشهر. تعود في 1 نوفمبر.');
    }

    public function test_two_credits_left_will_translate_but_not_write_a_description(): void
    {
        MenuTranslator::fake([['text' => 'شوربة']]);
        CategoryDescriptionWriter::fake()->preventStrayPrompts();
        $owner = $this->freeOwner();
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 498]);

        $this->actingAs($owner->user)->describe()->assertStatus(429)->assertJsonPath('code', 'writing_help_credits');
        $this->actingAs($owner->user)->translate()->assertOk()->assertJsonPath('data.credits_left', 1);
    }

    public function test_the_money_spent_leaves_no_credits_even_when_fewer_were_taken(): void
    {
        MenuTranslator::fake()->preventStrayPrompts();
        $owner = $this->freeOwner();
        // 100 of 500 taken, but the free package's $0.05 is all spent.
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 100, 'cost' => '0.049999']);

        $this->actingAs($owner->user)->translate()
            ->assertStatus(429)
            ->assertJsonPath('code', 'writing_help_credits')
            ->assertJsonPath('credits_left', 0)
            ->assertJsonPath('message', 'You have used this month\'s AI credits. They come back on 1 November.');
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help.credits.left', 0);
    }

    public function test_a_request_too_big_for_the_money_left_locks_the_buttons_till_the_page_opens_again(): void
    {
        MenuTranslator::fake()->preventStrayPrompts();
        $owner = $this->freeOwner();
        // $0.001 left buys 10 credits, but a 300-character translation could cost $0.0017.
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 100, 'cost' => '0.049000']);

        $this->actingAs($owner->user)->translate()
            ->assertStatus(429)
            ->assertJsonPath('credits_left', 0)
            ->assertJsonPath('message', 'Not enough AI credits left this month for this. They come back on 1 November.');
        // The session still says what is left: a small enough request goes.
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help.credits.left', 10);
    }

    public function test_too_few_for_a_description_says_so_and_how_many_are_left(): void
    {
        CategoryDescriptionWriter::fake()->preventStrayPrompts();
        $owner = $this->freeOwner();
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 498]);

        $this->actingAs($owner->user)->describe()
            ->assertStatus(429)
            ->assertJsonPath('credits_left', 2)
            ->assertJsonPath('message', 'Not enough AI credits left this month for this. They come back on 1 November.');
    }

    public function test_they_come_back_on_the_first(): void
    {
        MenuTranslator::fake([['text' => 'شوربة']]);
        $owner = $this->freeOwner();
        WritingHelpDay::factory()->for($owner)->create(['day' => '2026-10-20', 'credits' => 500]);

        $this->travelTo(Carbon::parse('2026-11-01 08:00', 'UTC'));

        $this->actingAs($owner->user)->translate()->assertOk()->assertJsonPath('data.credits_left', 499);
    }

    public function test_no_credits_is_no_writing_help(): void
    {
        Package::findBySlug('free')->update(['ai_budget_cents' => 0]);
        $owner = $this->freeOwner();

        $this->actingAs($owner->user)->translate()->assertNotFound();
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help', null);
    }

    public function test_no_cap_has_no_credits_left_to_count(): void
    {
        MenuTranslator::fake([['text' => 'شوربة']]);
        Package::findBySlug('free')->update(['ai_budget_cents' => null]);

        $this->actingAs($this->freeOwner()->user)->translate()->assertOk()->assertJsonPath('data.credits_left', null);
    }

    public function test_the_owner_is_never_told_money_or_the_model(): void
    {
        $owner = $this->freeOwner();

        $session = $this->actingAs($owner->user)->getJson(route('api.user'))->getContent();
        $packages = $this->actingAs($owner->user)->getJson(route('api.packages.index'))->getContent();

        foreach ([$session, $packages] as $sent) {
            $this->assertStringNotContainsString('ai_budget', $sent);
            $this->assertStringNotContainsString('cost', $sent);
            $this->assertStringNotContainsString('haiku', $sent);
        }
        $this->assertStringContainsString('"writing_help_credits":500', $packages);
    }
}
