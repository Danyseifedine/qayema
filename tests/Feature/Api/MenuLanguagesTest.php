<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

/**
 * The menu's second language and opening language, set from the Features page.
 */
class MenuLanguagesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

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
            ->getJson(route('api.settings.show'))
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

    public function test_both_fields_must_be_sent(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->save([])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['second_locale', 'default_locale']);
    }
}
