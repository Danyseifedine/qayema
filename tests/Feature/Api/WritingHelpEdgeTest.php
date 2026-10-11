<?php

namespace Tests\Feature\Api;

use App\Ai\Agents\CategoryDescriptionWriter;
use App\Ai\Agents\MenuTranslator;
use App\Ai\Agents\RestaurantDescriptionWriter;
use App\Enums\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The writing help's edges: what each request may carry, and the order of
 * PaceWritingHelp's answers (spent, busy, on).
 */
class WritingHelpEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private const CATEGORY_LINE = 'Warm bowls of soup to start your meal or share across the table.';

    private const RESTAURANT_LINE = 'Welcome to our table, where good food and good company come together for every kind of day and occasion.';

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
        $this->defaultPackageSets(Feature::WritingHelpLimit, 30);
        config([
            'services.writing_help.provider' => 'anthropic',
            'services.writing_help.at_once' => 10,
            'ai.providers.anthropic.key' => 'test-key',
        ]);
        MenuTranslator::fake(fn (): array => ['text' => 'Soupe']);
        CategoryDescriptionWriter::fake(fn (): array => ['text' => self::CATEGORY_LINE]);
        RestaurantDescriptionWriter::fake(fn (): array => ['text' => self::RESTAURANT_LINE]);
    }

    // ---- What each request may carry -------------------------------------

    /** @return array<string, array{0: string, 1: array<string, mixed>, 2: string|null}> */
    public static function requests(): array
    {
        $translate = ['text' => 'Soup', 'from' => 'en', 'to' => 'fr'];
        $category = ['name' => 'Soups', 'text' => null, 'language' => 'en'];
        $restaurant = ['text' => null, 'language' => 'en'];

        return [
            'translate: the longest text' => ['api.writing.translate', [...$translate, 'text' => str_repeat('a', 2000)], null],
            'translate: a text too long' => ['api.writing.translate', [...$translate, 'text' => str_repeat('a', 2001)], 'text'],
            'translate: no text' => ['api.writing.translate', [...$translate, 'text' => null], 'text'],
            'translate: a text that is not words' => ['api.writing.translate', [...$translate, 'text' => ['Soup']], 'text'],
            'translate: no language from' => ['api.writing.translate', [...$translate, 'from' => null], 'from'],
            'translate: a language from off the menu' => ['api.writing.translate', [...$translate, 'from' => 'de'], 'from'],
            'translate: no language to' => ['api.writing.translate', [...$translate, 'to' => null], 'to'],
            'translate: from the second language to the main one' => ['api.writing.translate', [...$translate, 'from' => 'fr', 'to' => 'en'], null],
            'translate: a field limit' => ['api.writing.translate', [...$translate, 'max' => 255], null],
            'translate: a field limit of none' => ['api.writing.translate', [...$translate, 'max' => 0], 'max'],
            'translate: a field limit past the longest text' => ['api.writing.translate', [...$translate, 'max' => 2001], 'max'],
            'translate: a field limit that is not a number' => ['api.writing.translate', [...$translate, 'max' => 'long'], 'max'],
            'translate: a category description' => ['api.writing.translate', [...$translate, 'fit' => 'category'], null],
            'translate: a restaurant description' => ['api.writing.translate', [...$translate, 'fit' => 'restaurant'], null],
            'translate: a kind of description there is not' => ['api.writing.translate', [...$translate, 'fit' => 'dish'], 'fit'],
            'category: the longest name' => ['api.writing.category-description', [...$category, 'name' => str_repeat('a', 255)], null],
            'category: a name too long' => ['api.writing.category-description', [...$category, 'name' => str_repeat('a', 256)], 'name'],
            'category: no name' => ['api.writing.category-description', [...$category, 'name' => ''], 'name'],
            'category: the longest own text' => ['api.writing.category-description', [...$category, 'text' => str_repeat('a', 300)], null],
            'category: an own text too long' => ['api.writing.category-description', [...$category, 'text' => str_repeat('a', 301)], 'text'],
            'category: no language' => ['api.writing.category-description', [...$category, 'language' => null], 'language'],
            'category: in the second language' => ['api.writing.category-description', [...$category, 'language' => 'fr'], null],
            'category: a language off the menu' => ['api.writing.category-description', [...$category, 'language' => 'ar'], 'language'],
            'restaurant: the longest own text' => ['api.writing.restaurant-description', [...$restaurant, 'text' => str_repeat('a', 2000)], null],
            'restaurant: an own text too long' => ['api.writing.restaurant-description', [...$restaurant, 'text' => str_repeat('a', 2001)], 'text'],
            'restaurant: no language' => ['api.writing.restaurant-description', [...$restaurant, 'language' => ''], 'language'],
            'restaurant: a language off the menu' => ['api.writing.restaurant-description', [...$restaurant, 'language' => 'tr'], 'language'],
        ];
    }

    #[DataProvider('requests')]
    public function test_what_each_request_may_carry(string $route, array $body, ?string $invalid): void
    {
        $owner = $this->owner(['second_locale' => 'fr']);

        $response = $this->actingAs($owner->user)->postJson(route($route), $body);

        $invalid === null
            ? $response->assertOk()
            : $response->assertUnprocessable()->assertJsonValidationErrors([$invalid]);
    }

    public function test_a_menu_in_one_language_has_nothing_to_translate_into(): void
    {
        $owner = $this->owner(['second_locale' => null]);

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.translate'), ['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['to']);
    }

    public function test_a_second_language_switched_off_is_not_a_language_to_write_in(): void
    {
        $owner = $this->owner(['second_locale' => 'fr', 'switched_off' => ['languages']]);

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'fr'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['language']);
    }

    /** @return array<string, array{0: string}> */
    public static function routes(): array
    {
        return [
            'translate' => ['api.writing.translate'],
            'a category description' => ['api.writing.category-description'],
            'the restaurant description' => ['api.writing.restaurant-description'],
        ];
    }

    #[DataProvider('routes')]
    public function test_a_guest_is_asked_to_sign_in(string $route): void
    {
        $this->postJson(route($route), [])->assertUnauthorized();
    }

    #[DataProvider('routes')]
    public function test_an_account_with_no_restaurant_is_refused_and_nothing_is_asked(string $route): void
    {
        $this->actingAs($this->userWithoutRestaurant())->postJson(route($route), [])->assertForbidden();

        MenuTranslator::assertNeverPrompted();
        CategoryDescriptionWriter::assertNeverPrompted();
        RestaurantDescriptionWriter::assertNeverPrompted();
    }

    // ---- PaceWritingHelp's order -----------------------------------------

    public function test_a_spent_day_is_said_before_busy(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        $owner = $this->owner();
        $this->actingAs($owner->user)->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])->assertOk();
        Cache::lock("writing-help:restaurant:{$owner->id}", 30)->get();

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertTooManyRequests()
            ->assertJsonPath('code', 'writing_help_limit');
    }

    public function test_writing_help_that_is_off_is_off_even_when_busy(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 0);
        $owner = $this->owner();
        Cache::lock("writing-help:restaurant:{$owner->id}", 30)->get();

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertNotFound();
    }

    public function test_the_server_uses_any_free_place_not_only_the_first(): void
    {
        config(['services.writing_help.at_once' => 3]);
        Cache::lock('writing-help:place:0', 30)->get();
        Cache::lock('writing-help:place:1', 30)->get();
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertOk();

        // Its place, the third, is free again.
        $this->assertTrue(Cache::lock('writing-help:place:2', 30)->get());
    }

    public function test_a_server_set_to_none_at_once_still_runs_one(): void
    {
        config(['services.writing_help.at_once' => 0]);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertOk();
    }

    public function test_a_failure_lets_go_of_the_turn_and_the_place(): void
    {
        config(['services.writing_help.at_once' => 1]);
        $tries = 0;
        MenuTranslator::fake(function () use (&$tries): array {
            if (++$tries === 1) {
                throw ProviderOverloadedException::forProvider('anthropic');
            }

            return ['text' => 'Soupe'];
        });
        $owner = $this->owner(['second_locale' => 'fr']);

        $this->actingAs($owner->user)->postJson(route('api.writing.translate'), ['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])->assertStatus(503);
        $this->actingAs($owner->user)->postJson(route('api.writing.translate'), ['text' => 'Soup', 'from' => 'en', 'to' => 'fr'])
            ->assertOk()
            ->assertJsonPath('data.left', 29);
    }

    public function test_a_busy_restaurant_does_not_hold_up_another(): void
    {
        $owner = $this->owner();
        $other = $this->owner();
        Cache::lock("writing-help:restaurant:{$owner->id}", 30)->get();

        $this->actingAs($other->user)
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertOk();
    }

    public function test_the_busy_answer_speaks_arabic_to_an_arabic_dashboard(): void
    {
        $owner = $this->owner();
        Cache::lock("writing-help:restaurant:{$owner->id}", 30)->get();

        $this->actingAs($owner->user)
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertStatus(503)
            ->assertJsonPath('message', 'المساعدة في الكتابة مشغولة الآن. حاول مرة أخرى بعد قليل.');
    }

    public function test_the_spent_answer_speaks_arabic_to_an_arabic_dashboard(): void
    {
        $this->defaultPackageSets(Feature::WritingHelpLimit, 1);
        $owner = $this->owner();
        $this->actingAs($owner->user)->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en']);

        $this->actingAs($owner->user)
            ->withHeader('Accept-Language', 'ar')
            ->postJson(route('api.writing.category-description'), ['name' => 'Soups', 'language' => 'en'])
            ->assertTooManyRequests()
            ->assertJsonPath('message', 'استخدمت المساعدة في الكتابة المتاحة لليوم. تعود غدًا.');
    }
}
