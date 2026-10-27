<?php

namespace Tests\Feature\Api;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Models\FeatureGrant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Testing\TestResponse;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Prompts\AgentPrompt;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The dashboard's AI buttons (App\Services\Ai\WritingHelp): on only while a
 * provider with a key is set, capped per restaurant per day, and always an
 * answer for the owner to check, never a save.
 */
class WritingHelpTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        config([
            'services.writing_help.provider' => 'anthropic',
            'services.writing_help.model' => null,
            'services.writing_help.at_once' => 10,
            'ai.providers.anthropic.key' => 'test-key',
        ]);
    }

    private function translate(array $body): TestResponse
    {
        return $this->postJson(route('api.writing.translate'), $body);
    }

    private function describe(array $body): TestResponse
    {
        return $this->postJson(route('api.writing.category-description'), $body);
    }

    private function describeRestaurant(array $body): TestResponse
    {
        return $this->postJson(route('api.writing.restaurant-description'), $body);
    }

    public function test_menu_text_is_translated_into_the_menus_other_language(): void
    {
        MenuTranslator::fake([['text' => '  سلطة الفتوش ']]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translate(['text' => 'Fattoush salad', 'from' => 'en', 'to' => 'ar'])
            ->assertOk()
            ->assertExactJson(['data' => ['text' => 'سلطة الفتوش', 'left' => 9, 'credits_left' => 499]]);

        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->prompt === 'Fattoush salad'
            && $prompt->timeout === 10
            && $prompt->agent->from === 'English'
            && $prompt->agent->to === 'العربية');
    }

    public function test_only_the_menus_own_languages_and_never_into_the_same_one(): void
    {
        MenuTranslator::fake();
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
        $this->actingAs($owner->user)
            ->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'en'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
        $this->actingAs($owner->user)
            ->translate(['text' => '', 'from' => 'en', 'to' => 'ar'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['text']);

        MenuTranslator::assertNeverPrompted();
    }

    public function test_a_category_description_is_written_from_its_name(): void
    {
        CategoryDescriptionWriter::fake([['text' => 'Warm bowls of soup to start your meal or share across the table.']]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'text' => null, 'language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Warm bowls of soup to start your meal or share across the table.');

        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Category: Soups')
            && $prompt->contains('Write its description.')
            && $prompt->agent->language === 'English');
    }

    public function test_the_owners_own_description_is_improved(): void
    {
        CategoryDescriptionWriter::fake([['text' => 'Our soups, made every morning, to warm you up before the meal.']]);
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->describe(['name' => 'شوربات', 'text' => 'شوربة كل يوم', 'language' => 'ar'])
            ->assertOk();

        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains("Improve this description:\nشوربة كل يوم")
            && $prompt->agent->language === 'العربية');
    }

    public function test_a_description_of_the_wrong_length_is_asked_again(): void
    {
        CategoryDescriptionWriter::fake([
            ['text' => 'Soups to start.'],
            ['text' => 'Warm bowls of soup to start your meal or share across the table.'],
        ]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Warm bowls of soup to start your meal or share across the table.');

        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Your last answer, "Soups to start.", was 15 characters. Write it again in 60 to 70 characters.'));
    }

    public function test_of_a_writers_versions_the_first_that_keeps_every_rule_wins(): void
    {
        CategoryDescriptionWriter::fake([
            ['texts' => ['Soups to start.', 'Soups made fresh every morning, to start your meal well.', 'Warm bowls of soup to start your meal or share across the table.']],
        ]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Warm bowls of soup to start your meal or share across the table.');
    }

    public function test_versions_that_all_miss_are_asked_again_with_their_lengths(): void
    {
        CategoryDescriptionWriter::fake([
            ['texts' => ['Soups.', 'Soups to start the meal.']],
            ['texts' => ['Warm bowls of soup to start your meal or share across the table.']],
        ]);
        $owner = $this->owner();

        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();

        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('Your last versions were 6, 24 characters long; none was 60 to 70 characters.'));
    }

    public function test_never_a_description_of_the_wrong_length(): void
    {
        CategoryDescriptionWriter::fake([
            ['text' => 'Soups.'],
            ['text' => str_repeat('Soup ', 20)],
            ['text' => 'Soups to start the meal.'],
            ['text' => 'Warm bowls of soup to start your meal or share across the table.'],
        ]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'writing_help_failed');
    }

    public function test_a_description_never_carries_a_dash(): void
    {
        CategoryDescriptionWriter::fake([['text' => "Slow-cooked soups \u{2014} warm bowls \u{2013} made to start your meal and linger over"]]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Slow cooked soups, warm bowls, made to start your meal and linger over');

        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->agent->shortest === 60
            && $prompt->agent->longest === 70);
    }

    public function test_the_restaurant_is_described_from_its_menu_in_the_language_asked(): void
    {
        RestaurantDescriptionWriter::fake([['text' => 'Pizzas and salads for every appetite, made for long lunches and easy dinners shared with the people you love.']]);
        $owner = $this->owner(['name' => ['en' => 'Olive', 'ar' => 'زيتون'], 'second_locale' => 'ar']);
        $pizza = Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => 'Pizza', 'ar' => 'بيتزا'], 'display_order' => 1]);
        $salads = Category::factory()->create(['restaurant_id' => $owner->id, 'name' => ['en' => 'Salads'], 'display_order' => 2]);
        Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $pizza->id, 'name' => ['en' => 'Margherita', 'ar' => 'مارغريتا'], 'display_order' => 1]);
        Dish::factory()->create(['restaurant_id' => $owner->id, 'category_id' => $pizza->id, 'name' => ['en' => 'Pepperoni'], 'display_order' => 2]);

        $this->actingAs($owner->user)
            ->describeRestaurant(['text' => null, 'language' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Pizzas and salads for every appetite, made for long lunches and easy dinners shared with the people you love.');

        // Where the menu has no Arabic yet, its main language stands in.
        RestaurantDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains("Restaurant: زيتون\nIts menu:\nبيتزا: مارغريتا, Pepperoni\nSalads\nWrite its description.")
            && $prompt->agent->language === 'العربية'
            && $prompt->agent->shortest === 100
            && $prompt->agent->longest === 110);
    }

    public function test_an_empty_menu_still_gets_a_line_and_the_owners_own_is_improved(): void
    {
        RestaurantDescriptionWriter::fake([['text' => 'Welcome to our table, where good food and good company come together for every kind of day and occasion.']]);
        $owner = $this->owner(['name' => ['en' => 'Olive']]);

        $this->actingAs($owner->user)
            ->describeRestaurant(['text' => 'grills since 2010', 'language' => 'en'])
            ->assertOk();

        RestaurantDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains("Restaurant: Olive\nIts menu is empty so far.\nImprove this description:\ngrills since 2010"));
    }

    public function test_a_restaurant_description_is_held_to_its_length(): void
    {
        RestaurantDescriptionWriter::fake([
            ['text' => 'Welcome to Olive.'],
            ['text' => 'Welcome to our table, where good food and good company come together for every kind of day and occasion.'],
        ]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describeRestaurant(['language' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.text', 'Welcome to our table, where good food and good company come together for every kind of day and occasion.');

        RestaurantDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->contains('was 17 characters. Write it again in 100 to 110 characters.'));
    }

    public function test_a_restaurant_description_only_in_the_menus_languages(): void
    {
        RestaurantDescriptionWriter::fake();
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describeRestaurant(['language' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language']);

        RestaurantDescriptionWriter::assertNeverPrompted();
    }

    public function test_a_description_keeps_to_its_time_budget(): void
    {
        // Each answer takes 8 seconds and is too short: the second try gets
        // what is left of the 15, and a third is never started.
        $this->freezeTime();
        $tries = 0;
        CategoryDescriptionWriter::fake(function () use (&$tries): array {
            $tries++;
            $this->travel(8)->seconds();

            return ['text' => 'Too short.'];
        });
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'writing_help_failed');

        $this->assertSame(2, $tries);
        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->timeout === 10 && ! $prompt->contains('Your last answer'));
        CategoryDescriptionWriter::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->timeout === 7 && $prompt->contains('Your last answer'));
    }

    public function test_one_request_at_a_time_per_restaurant(): void
    {
        CategoryDescriptionWriter::fake(fn (): array => ['text' => 'Warm bowls of soup to start your meal or share across the table.']);
        $owner = $this->owner();
        $other = $this->owner();
        $running = Cache::lock("writing-help:restaurant:{$owner->id}", 30);
        $running->get();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Writing help is busy right now. Try again in a moment.',
                'code' => 'writing_help_busy',
            ]);
        $this->actingAs($other->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();

        $running->release();
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
    }

    public function test_the_server_runs_only_so_many_at_once_and_frees_its_places(): void
    {
        config(['services.writing_help.at_once' => 1]);
        RestaurantDescriptionWriter::fake(fn (): array => ['text' => 'Welcome to our table, where good food and good company come together for every kind of day and occasion.']);
        $owner = $this->owner();
        $elsewhere = Cache::lock('writing-help:place:0', 30);
        $elsewhere->get();

        $this->actingAs($owner->user)
            ->describeRestaurant(['language' => 'en'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'writing_help_busy');

        $elsewhere->release();
        $this->actingAs($owner->user)->describeRestaurant(['language' => 'en'])->assertOk();
        // Done, it let go of its place and its turn.
        $this->actingAs($owner->user)->describeRestaurant(['language' => 'en'])->assertOk();
    }

    public function test_a_busy_answer_costs_none_of_the_days_uses(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        CategoryDescriptionWriter::fake(fn (): array => ['text' => 'Warm bowls of soup to start your meal or share across the table.']);
        $owner = $this->owner();
        $running = Cache::lock("writing-help:restaurant:{$owner->id}", 30);
        $running->get();

        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertStatus(503);

        $running->release();
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertTooManyRequests();
    }

    public function test_the_model_is_the_configured_one_or_else_the_providers_cheapest(): void
    {
        MenuTranslator::fake([['text' => 'Soupe'], ['text' => 'Soupe']]);
        // No cap on its AI: the most a Sonnet call could cost is more than
        // the free package's whole month (WritingHelpCreditsTest).
        $this->defaultPackageAiBudget(null);
        $owner = $this->owner(['second_locale' => 'fr']);

        $this->actingAs($owner->user)->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])->assertOk();
        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->model === $prompt->provider->cheapestTextModel());

        config(['services.writing_help.model' => 'claude-sonnet-5-5']);
        $this->actingAs($owner->user)->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])->assertOk();
        MenuTranslator::assertPrompted(fn (AgentPrompt $prompt): bool => $prompt->model === 'claude-sonnet-5-5');
    }

    public function test_without_a_provider_or_its_key_there_is_no_writing_help(): void
    {
        MenuTranslator::fake();
        $owner = $this->owner(['second_locale' => 'ar']);

        config(['ai.providers.anthropic.key' => null]);
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help', null);
        $this->actingAs($owner->user)->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'ar'])->assertNotFound();

        config(['ai.providers.anthropic.key' => 'test-key', 'services.writing_help.provider' => null]);
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help', null);
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertNotFound();
        $this->actingAs($owner->user)->describeRestaurant(['language' => 'en'])->assertNotFound();

        config(['services.writing_help.provider' => 'anthropic']);
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help.limit', 10)->assertJsonPath('data.restaurant.writing_help.left', 10);

        MenuTranslator::assertNeverPrompted();
    }

    public function test_a_provider_that_fails_is_one_clear_answer_to_try_again(): void
    {
        MenuTranslator::fake(fn () => throw ProviderOverloadedException::forProvider('anthropic'));
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'ar'])
            ->assertStatus(503)
            ->assertExactJson([
                'message' => 'Writing help is not available right now. Try again in a moment.',
                'code' => 'writing_help_failed',
            ]);
    }

    public function test_an_empty_answer_is_a_failure_not_a_blank_field(): void
    {
        CategoryDescriptionWriter::fake([['text' => '   ']]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertStatus(503)
            ->assertJsonPath('code', 'writing_help_failed');
    }

    public function test_a_package_with_no_daily_uses_has_no_writing_help(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 0);
        CategoryDescriptionWriter::fake();
        $owner = $this->owner();

        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help', null);
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertNotFound();
        $this->actingAs($owner->user)->describeRestaurant(['language' => 'en'])->assertNotFound();

        CategoryDescriptionWriter::assertNeverPrompted();
    }

    public function test_an_unlimited_package_has_no_daily_cap(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, null);
        CategoryDescriptionWriter::fake(fn (): array => ['text' => 'Warm bowls of soup to start your meal or share across the table.']);
        $owner = $this->owner();

        foreach (range(1, 5) as $use) {
            $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
        }
        $this->actingAs($owner->user)->getJson(route('api.user'))->assertJsonPath('data.restaurant.writing_help.limit', null)->assertJsonPath('data.restaurant.writing_help.left', null);
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertJsonPath('data.left', null);
    }

    public function test_a_grant_raises_one_restaurants_daily_uses(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        CategoryDescriptionWriter::fake(fn (): array => ['text' => 'Warm bowls of soup to start your meal or share across the table.']);
        $owner = $this->owner();
        FeatureGrant::factory()->create([
            'restaurant_id' => $owner->id,
            'feature' => Feature::WritingHelpLimit->value,
            'value' => 2,
        ]);

        // A grant adds to the package's number: 1 + 2.
        foreach (range(1, 3) as $use) {
            $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
        }
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertTooManyRequests();
    }

    public function test_each_answer_and_the_session_say_what_is_left(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 3);
        CategoryDescriptionWriter::fake(fn (): array => ['text' => 'Warm bowls of soup to start your meal or share across the table.']);
        $owner = $this->owner();

        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertJsonPath('data.left', 2);
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertJsonPath('data.left', 1);
        $this->actingAs($owner->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.writing_help.limit', 3)->assertJsonPath('data.restaurant.writing_help.left', 1);
    }

    public function test_once_spent_the_answer_says_none_left_and_when_they_come_back(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        CategoryDescriptionWriter::fake(fn (): array => ['text' => 'Warm bowls of soup to start your meal or share across the table.']);
        $owner = $this->owner(['timezone' => 'Asia/Tokyo']);
        $this->travelTo(Carbon::parse('2026-10-11 21:00', 'Asia/Tokyo'));

        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertJsonPath('data.left', 0);
        $this->travel(1)->hours();

        // Back at the restaurant's midnight, two hours away.
        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'writing_help_limit')
            ->assertJsonPath('left', 0)
            ->assertJsonPath('retry_after', 2 * 3600)
            ->assertHeader('Retry-After', (string) (2 * 3600));

        $this->travel(2)->hours();
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
    }

    public function test_a_use_that_came_to_nothing_is_given_back(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 2);
        MenuTranslator::fake(fn () => throw ProviderOverloadedException::forProvider('anthropic'));
        $owner = $this->owner(['second_locale' => 'ar']);

        // The provider failed, then the request was refused: neither counts.
        $this->actingAs($owner->user)->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'ar'])->assertStatus(503);
        $this->actingAs($owner->user)->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])->assertUnprocessable();

        $this->actingAs($owner->user)->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.writing_help.limit', 2)->assertJsonPath('data.restaurant.writing_help.left', 2);
    }

    public function test_each_restaurant_has_its_own_daily_allowance(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 2);
        CategoryDescriptionWriter::fake(fn (): array => ['text' => 'Warm bowls of soup to start your meal or share across the table.']);
        $owner = $this->owner();
        $other = $this->owner();

        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
        $this->actingAs($owner->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
        $this->actingAs($owner->user)
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'writing_help_limit')
            ->assertJsonPath('message', 'You have used today\'s writing help. It comes back tomorrow.');

        $this->actingAs($other->user)->describe(['name' => 'Soups', 'language' => 'en'])->assertOk();
    }

    public function test_only_a_signed_in_owner_with_a_restaurant(): void
    {
        $this->translate(['text' => 'Soup', 'from' => 'en', 'to' => 'ar'])->assertUnauthorized();
        $this->actingAs($this->userWithoutRestaurant())
            ->describe(['name' => 'Soups', 'language' => 'en'])
            ->assertForbidden();
    }
}
