<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The menu's second language and opening language, set from the Features page.
 */
class MenuLanguagesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    private function save(array $languages)
    {
        return $this->putJson(route('api.menu-languages.update'), $languages);
    }

    public function test_an_owner_picks_a_second_language_and_where_the_menu_opens(): void
    {
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->save(['second_locale' => 'fr', 'default_locale' => 'fr'])
            ->assertOk()
            ->assertJsonPath('data.languages', ['en', 'fr'])
            ->assertJsonPath('data.default_locale', 'fr');

        $this->assertSame('fr', $owner->fresh()->second_locale);
    }

    public function test_changing_the_second_language_keeps_what_was_written_in_the_old_one(): void
    {
        $owner = $this->owner(['second_locale' => 'ar', 'name' => ['en' => 'Olive', 'ar' => 'زيتون']]);

        $this->actingAs($owner->user)->save(['second_locale' => 'fr', 'default_locale' => 'en'])->assertOk();
        $this->assertSame('زيتون', $owner->fresh()->getTranslation('name', 'ar', false));

        $this->actingAs($owner->user)->save(['second_locale' => 'ar', 'default_locale' => 'en'])->assertOk();
        $this->actingAs($owner->user)
            ->getJson(route('api.restaurant.show'))
            ->assertJsonPath('data.name', ['en' => 'Olive', 'ar' => 'زيتون']);
    }

    public function test_the_menu_cannot_open_in_a_language_it_is_not_written_in(): void
    {
        $owner = $this->owner(['second_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->save(['second_locale' => 'ar', 'default_locale' => 'fr'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('default_locale');
    }

    public function test_only_a_language_from_the_list_can_be_the_second_one(): void
    {
        $owner = $this->owner();

        foreach (['en', 'xx', 'klingon'] as $bad) {
            $this->actingAs($owner->user)
                ->save(['second_locale' => $bad, 'default_locale' => 'en'])
                ->assertStatus(422, $bad)
                ->assertJsonValidationErrors('second_locale');
        }
    }

    public function test_no_second_language_means_an_english_menu(): void
    {
        $owner = $this->owner(['second_locale' => 'ar', 'default_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->save(['second_locale' => null, 'default_locale' => 'en'])
            ->assertOk()
            ->assertJsonPath('data.languages', ['en'])
            ->assertJsonPath('data.default_locale', 'en');
    }

    public function test_an_owner_makes_the_second_language_the_main_one_and_nothing_is_lost(): void
    {
        $owner = $this->owner(['second_locale' => 'ar', 'name' => ['en' => 'Olive', 'ar' => 'زيتون']]);

        $this->actingAs($owner->user)
            ->save(['main_locale' => 'ar', 'second_locale' => 'en', 'default_locale' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.languages', ['ar', 'en'])
            ->assertJsonPath('data.main_locale', 'ar');

        $this->actingAs($owner->user)
            ->getJson(route('api.restaurant.show'))
            ->assertJsonPath('data.name', ['ar' => 'زيتون', 'en' => 'Olive']);
    }

    public function test_a_package_with_one_language_can_still_choose_which(): void
    {
        // Free: one language.
        $this->defaultPackageSets(Feature::MultipleLanguages, 0);
        $owner = $this->owner(['second_locale' => null]);

        $this->actingAs($owner->user)
            ->save(['main_locale' => 'ar', 'second_locale' => null, 'default_locale' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.languages', ['ar'])
            ->assertJsonPath('data.default_locale', 'ar');

        // A second one still needs the package.
        $this->actingAs($owner->user)
            ->save(['main_locale' => 'ar', 'second_locale' => 'en', 'default_locale' => 'ar'])
            ->assertForbidden();
    }

    public function test_the_second_language_cannot_be_the_main_one(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->save(['main_locale' => 'fr', 'second_locale' => 'fr', 'default_locale' => 'fr'])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['second_locale' => __('The second language must differ from the main one.')]);
    }

    public function test_only_a_language_from_the_list_can_be_the_main_one(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->save(['main_locale' => 'xx', 'second_locale' => null, 'default_locale' => 'en'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('main_locale');
    }

    public function test_it_says_what_has_no_name_in_a_new_main_language(): void
    {
        $owner = $this->owner(['second_locale' => null]);
        Category::factory()->for($owner)->create(['name' => ['en' => 'Soup']]);
        Dish::factory()->for($owner)->count(2)->create(['name' => ['en' => 'Kafta']]);

        $this->actingAs($owner->user)
            ->save(['main_locale' => 'fr', 'second_locale' => null, 'default_locale' => 'fr'])
            ->assertOk()
            ->assertJsonPath('data.missing', ['categories' => 1, 'dishes' => 2]);

        $this->actingAs($owner->user)
            ->getJson(route('api.menu-languages.show'))
            ->assertOk()
            ->assertJsonPath('data.main_locale', 'fr')
            ->assertJsonPath('data.missing', ['categories' => 1, 'dishes' => 2]);
    }

    public function test_without_a_main_language_sent_it_stays_as_it_is(): void
    {
        $owner = $this->owner(['main_locale' => 'ar', 'default_locale' => 'ar']);

        $this->actingAs($owner->user)
            ->save(['second_locale' => 'fr', 'default_locale' => 'ar'])
            ->assertOk()
            ->assertJsonPath('data.languages', ['ar', 'fr']);
    }

    public function test_both_fields_must_be_sent(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->save([])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['second_locale', 'default_locale']);
    }
}
