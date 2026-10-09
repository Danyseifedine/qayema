<?php

namespace Tests\Integration\Services\Menu;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use App\Services\Menu\MenuLanguages;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class MenuLanguagesTest extends TestCase
{
    use CreatesOwners;
    use RefreshDatabase;

    /**
     * @param  array<string, string>  $names
     */
    private function category(array $names): Category
    {
        $category = new Category;
        $category->setTranslations('name', $names);

        return $category;
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function request(array $body): Request
    {
        return Request::create('/api/categories', 'POST', server: ['CONTENT_TYPE' => 'application/json'], content: json_encode($body));
    }

    public function test_the_catalogue_is_the_menu_list_in_config(): void
    {
        $this->assertSame(config('locales.menu'), MenuLanguages::catalogue());
        $this->assertArrayHasKey(MenuLanguages::DEFAULT_MAIN, MenuLanguages::catalogue());
    }

    public function test_the_catalogue_is_empty_when_config_has_none(): void
    {
        config()->set('locales', array_diff_key(config('locales'), ['menu' => true]));

        $this->assertSame([], MenuLanguages::catalogue());
        $this->assertSame([], MenuLanguages::choices());
    }

    public function test_every_language_on_the_list_can_be_the_main_one(): void
    {
        $this->assertSame(['en', 'ar', 'fr', 'es', 'tr', 'de', 'it', 'ru', 'zh', 'hi', 'pt'], MenuLanguages::choices());
    }

    public function test_the_main_language_is_the_restaurants_and_english_for_one_off_the_list(): void
    {
        $this->assertSame('ar', MenuLanguages::main($this->owner(['main_locale' => 'ar'])));
        $this->assertSame('en', MenuLanguages::main($this->owner(['main_locale' => 'xx'])));
    }

    public function test_a_single_language_menu_can_be_in_any_language(): void
    {
        // No multiple_languages on the default package: one language, the main one.
        $restaurant = $this->owner(['main_locale' => 'ar', 'second_locale' => 'en', 'default_locale' => 'en']);

        $this->assertSame(['ar'], MenuLanguages::for($restaurant));
        $this->assertSame(['ar', 'en'], MenuLanguages::written($restaurant));
        $this->assertSame('ar', MenuLanguages::default($restaurant));
    }

    public function test_a_second_language_equal_to_the_main_one_is_ignored(): void
    {
        $this->assertSame(['fr'], MenuLanguages::written($this->owner(['main_locale' => 'fr', 'second_locale' => 'fr'])));
    }

    public function test_the_main_language_carries_the_required_rule_wherever_it_sits_in_the_alphabet(): void
    {
        $this->assertSame(
            ['name.fr' => ['required', 'string', 'max:255'], 'name.en' => ['nullable', 'string', 'max:255']],
            MenuLanguages::rules('name', ['fr', 'en'], 255, 'required'),
        );
    }

    public function test_choosing_the_second_language_as_main_swaps_the_two(): void
    {
        $restaurant = $this->owner(['main_locale' => 'en', 'second_locale' => 'ar', 'default_locale' => 'en']);

        $this->assertSame(
            ['main_locale' => 'ar', 'second_locale' => 'en', 'default_locale' => 'ar'],
            MenuLanguages::withMain($restaurant, 'ar'),
        );
    }

    public function test_choosing_a_new_main_language_keeps_the_second_and_its_opening(): void
    {
        $restaurant = $this->owner(['main_locale' => 'en', 'second_locale' => 'ar', 'default_locale' => 'ar']);

        $this->assertSame(
            ['main_locale' => 'fr', 'second_locale' => 'ar', 'default_locale' => 'ar'],
            MenuLanguages::withMain($restaurant, 'fr'),
        );
    }

    public function test_text_falls_back_to_the_main_language_then_to_whatever_was_written(): void
    {
        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة']);

        $this->assertSame('شوربة', MenuLanguages::text($category, 'name', 'fr', 'ar'));
        // A new main language not written yet: the old name rather than nothing.
        $this->assertSame('Soup', MenuLanguages::text($this->category(['en' => 'Soup']), 'name', 'fr', 'fr'));
    }

    public function test_it_counts_what_has_no_name_in_the_main_language(): void
    {
        $restaurant = $this->owner(['main_locale' => 'fr']);
        Category::factory()->for($restaurant)->create(['name' => ['en' => 'Soup']]);
        Category::factory()->for($restaurant)->create(['name' => ['fr' => 'Soupe']]);
        Dish::factory()->for($restaurant)->create(['name' => ['en' => 'Kafta']]);

        $this->assertSame(['categories' => 1, 'dishes' => 1], MenuLanguages::missingInMain($restaurant));
    }

    public function test_a_package_without_multiple_languages_shows_english_only(): void
    {
        $restaurant = $this->owner(['second_locale' => 'ar']);

        $this->assertSame(['en'], MenuLanguages::for($restaurant));
        $this->assertSame(['en', 'ar'], MenuLanguages::written($restaurant), 'The second language is remembered.');
    }

    public function test_with_the_feature_the_menu_shows_english_then_the_second_language(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $this->assertSame(['en', 'ar'], MenuLanguages::for($this->owner(['second_locale' => 'ar'])));
        $this->assertSame(['en', 'fr'], MenuLanguages::for($this->owner(['second_locale' => 'fr'])));
    }

    public function test_switching_languages_off_makes_the_menu_english_only_without_forgetting(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $restaurant = $this->owner(['second_locale' => 'ru', 'switched_off' => ['languages']]);

        $this->assertSame(['en'], MenuLanguages::for($restaurant));
        $this->assertSame(['en', 'ru'], MenuLanguages::written($restaurant));
    }

    public function test_switching_off_another_feature_leaves_the_languages_alone(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $this->assertSame(['en', 'ar'], MenuLanguages::for($this->owner(['switched_off' => ['orders', 'qr']])));
    }

    public function test_no_second_language_is_english_only(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $restaurant = $this->owner(['second_locale' => null]);

        $this->assertSame(['en'], MenuLanguages::for($restaurant));
        $this->assertSame(['en'], MenuLanguages::written($restaurant));
    }

    public function test_a_second_language_outside_the_catalogue_is_ignored(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $this->assertSame(['en'], MenuLanguages::written($this->owner(['second_locale' => 'xx'])));
        $this->assertSame(['en'], MenuLanguages::written($this->owner(['second_locale' => 'en'])), 'English is never listed twice.');
    }

    public function test_the_menu_opens_in_the_owners_default_while_it_is_shown(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $this->assertSame('ar', MenuLanguages::default($this->owner(['second_locale' => 'ar', 'default_locale' => 'ar'])));
        $this->assertSame('en', MenuLanguages::default($this->owner(['second_locale' => 'ar', 'default_locale' => 'en'])));
    }

    public function test_the_default_falls_back_to_english_when_it_is_not_shown(): void
    {
        $this->assertSame(
            'en',
            MenuLanguages::default($this->owner(['second_locale' => 'ar', 'default_locale' => 'ar'])),
            'No multiple_languages on the package.',
        );

        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $this->assertSame('en', MenuLanguages::default($this->owner([
            'second_locale' => 'ar', 'default_locale' => 'ar', 'switched_off' => ['languages'],
        ])));
        $this->assertSame('en', MenuLanguages::default($this->owner(['second_locale' => 'ar', 'default_locale' => 'fr'])));
        $this->assertSame('en', MenuLanguages::default($this->owner(['second_locale' => null, 'default_locale' => 'ar'])));
    }

    public function test_an_owners_languages_come_from_their_restaurant(): void
    {
        $this->defaultPackageIncludes(Feature::MultipleLanguages);

        $restaurant = $this->owner(['second_locale' => 'zh']);

        $this->assertSame(['en', 'zh'], MenuLanguages::forOwner($restaurant->user));
    }

    public function test_no_owner_or_no_restaurant_is_english_only(): void
    {
        $this->assertSame(['en'], MenuLanguages::forOwner(null));
        $this->assertSame(['en'], MenuLanguages::forOwner($this->userWithoutRestaurant()));
    }

    public function test_rules_give_every_language_a_bounded_string_and_english_its_own_rule(): void
    {
        $this->assertSame([
            'name.en' => ['required', 'string', 'max:120'],
            'name.ar' => ['nullable', 'string', 'max:120'],
            'name.fr' => ['nullable', 'string', 'max:120'],
        ], MenuLanguages::rules('name', ['en', 'ar', 'fr'], 120, 'required'));
    }

    public function test_rules_default_english_to_nullable(): void
    {
        $this->assertSame([
            'description.en' => ['nullable', 'string', 'max:300'],
        ], MenuLanguages::rules('description', ['en'], 300));

        $this->assertSame([], MenuLanguages::rules('name', [], 10));
    }

    public function test_rules_validate_as_intended(): void
    {
        $rules = MenuLanguages::rules('name', ['en', 'ar'], 5, 'required');

        $this->assertTrue(validator(['name' => ['en' => 'Soup']], $rules)->passes());
        $this->assertTrue(validator(['name' => ['en' => 'Soup', 'ar' => null]], $rules)->passes());
        $this->assertSame(['name.en'], validator(['name' => ['ar' => 'شوربة']], $rules)->errors()->keys());
        $this->assertSame(['name.ar'], validator(['name' => ['en' => 'Soup', 'ar' => 'طويل جداً']], $rules)->errors()->keys());
    }

    public function test_right_to_left_comes_from_the_catalogue(): void
    {
        $this->assertTrue(MenuLanguages::isRtl('ar'));
        $this->assertFalse(MenuLanguages::isRtl('en'));
        $this->assertFalse(MenuLanguages::isRtl('zh'));
        $this->assertFalse(MenuLanguages::isRtl('xx'), 'An unknown language is left to right.');
    }

    public function test_text_is_the_language_asked_for(): void
    {
        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة']);

        $this->assertSame('شوربة', MenuLanguages::text($category, 'name', 'ar'));
        $this->assertSame('Soup', MenuLanguages::text($category, 'name', 'en'));
    }

    public function test_text_falls_back_to_english_not_to_the_app_locale(): void
    {
        app()->setLocale('ar');

        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة']);

        $this->assertSame('Soup', MenuLanguages::text($category, 'name', 'fr'));
    }

    public function test_arabic_only_text_shows_under_an_english_app_locale(): void
    {
        app()->setLocale('en');

        $this->assertSame('شوربة', MenuLanguages::text($this->category(['ar' => 'شوربة']), 'name', 'ar'));
    }

    public function test_text_with_nothing_written_is_blank(): void
    {
        $this->assertSame('', MenuLanguages::text($this->category([]), 'name', 'ar'));
        $this->assertSame('', MenuLanguages::text($this->category(['fr' => '']), 'name', 'ar', 'fr'));
    }

    public function test_map_gives_every_language_asked_for_and_null_when_missing(): void
    {
        $category = $this->category(['en' => 'Soup', 'fr' => 'Soupe', 'de' => 'Suppe']);

        $this->assertSame(['en' => 'Soup', 'ar' => null, 'fr' => 'Soupe'], MenuLanguages::map($category, 'name', ['en', 'ar', 'fr']));
        $this->assertSame([], MenuLanguages::map($category, 'name', []));
    }

    public function test_map_does_not_fall_back(): void
    {
        $this->assertSame(['ar' => null], MenuLanguages::map($this->category(['en' => 'Soup']), 'name', ['ar']));
    }

    public function test_input_not_sent_is_null(): void
    {
        $this->assertNull(MenuLanguages::input($this->request(['price' => 5]), 'name', ['en', 'ar']));
    }

    public function test_input_sent_as_null_clears_every_active_language(): void
    {
        $this->assertSame(
            ['en' => '', 'ar' => ''],
            MenuLanguages::input($this->request(['description' => null]), 'description', ['en', 'ar']),
        );
    }

    public function test_input_that_is_not_a_map_is_read_as_a_clear(): void
    {
        $this->assertSame(['en' => ''], MenuLanguages::input($this->request(['name' => 'Soup']), 'name', ['en']));
    }

    public function test_input_sent_as_a_map_is_passed_through(): void
    {
        $this->assertSame(
            ['en' => 'Soup', 'ar' => 'شوربة', 'fr' => 'Soupe'],
            MenuLanguages::input($this->request(['name' => ['en' => 'Soup', 'ar' => 'شوربة', 'fr' => 'Soupe']]), 'name', ['en', 'ar']),
        );
    }

    public function test_fill_writes_trimmed_text_for_each_language_sent(): void
    {
        $category = $this->category(['en' => 'Old']);

        MenuLanguages::fill($category, 'name', ['en' => '  Soup  ', 'ar' => 'شوربة'], ['en', 'ar']);

        $this->assertSame(['en' => 'Soup', 'ar' => 'شوربة'], $category->getTranslations('name'));
    }

    public function test_fill_removes_a_language_sent_blank(): void
    {
        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة']);

        MenuLanguages::fill($category, 'name', ['ar' => '   '], ['en', 'ar']);

        $this->assertSame(['en' => 'Soup'], $category->getTranslations('name'));
    }

    public function test_fill_removes_a_language_sent_as_null(): void
    {
        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة']);

        MenuLanguages::fill($category, 'name', ['ar' => null], ['en', 'ar']);

        $this->assertSame(['en' => 'Soup'], $category->getTranslations('name'));
    }

    public function test_fill_leaves_a_language_not_sent_alone(): void
    {
        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة']);

        MenuLanguages::fill($category, 'name', ['en' => 'Broth'], ['en', 'ar']);

        $this->assertSame(['en' => 'Broth', 'ar' => 'شوربة'], $category->getTranslations('name'));
    }

    /** Text in a language the owner switched away from stays, hidden, for when it comes back. */
    public function test_fill_never_touches_a_language_that_is_not_active(): void
    {
        $category = $this->category(['en' => 'Soup', 'fr' => 'Soupe']);

        MenuLanguages::fill($category, 'name', ['en' => 'Broth', 'fr' => '', 'ar' => 'شوربة'], ['en', 'ar']);

        $this->assertSame(['en' => 'Broth', 'fr' => 'Soupe', 'ar' => 'شوربة'], $category->getTranslations('name'));
    }

    public function test_fill_with_no_input_changes_nothing(): void
    {
        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة']);

        MenuLanguages::fill($category, 'name', null, ['en', 'ar']);

        $this->assertSame(['en' => 'Soup', 'ar' => 'شوربة'], $category->getTranslations('name'));
    }

    public function test_input_then_fill_with_null_clears_every_active_language_and_keeps_the_rest(): void
    {
        $category = $this->category(['en' => 'Soup', 'ar' => 'شوربة', 'fr' => 'Soupe']);

        MenuLanguages::fill(
            $category,
            'name',
            MenuLanguages::input($this->request(['name' => null]), 'name', ['en', 'ar']),
            ['en', 'ar'],
        );

        $this->assertSame(['fr' => 'Soupe'], $category->getTranslations('name'));
    }
}
