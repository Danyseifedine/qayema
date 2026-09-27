<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class SectionsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_every_section_shows_until_the_owner_hides_one(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.hidden_sections', []);
    }

    public function test_an_owner_can_hide_and_show_sections(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->putJson(route('api.sections.update'), ['hidden' => ['orders', 'analytics']])
            ->assertOk()
            ->assertJsonPath('data.hidden', ['orders', 'analytics']);

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.hidden_sections', ['orders', 'analytics']);

        $this->actingAs($owner->user)
            ->putJson(route('api.sections.update'), ['hidden' => []])
            ->assertOk()
            ->assertJsonPath('data.hidden', []);
    }

    public function test_core_sections_cannot_be_hidden(): void
    {
        $owner = $this->owner();

        foreach (['overview', 'dishes', 'settings', 'package', 'social-links', 'nonsense'] as $section) {
            $this->actingAs($owner->user)
                ->putJson(route('api.sections.update'), ['hidden' => [$section]])
                ->assertStatus(422, $section)
                ->assertJsonValidationErrors('hidden.0');
        }
    }

    public function test_the_list_must_be_sent(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->putJson(route('api.sections.update'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hidden');
    }

    public function test_switching_orders_off_stops_the_menu_taking_them(): void
    {
        \App\Models\Package::default()->setFeature(\App\Enums\Feature::Ordering, 1);
        $owner = $this->published(['slug' => 'olive', 'hidden_sections' => ['orders']]);
        $dish = \App\Models\Dish::factory()->for($owner)->create(['price' => '5.00']);

        $this->postJson(route('public.order', $owner->slug), ['items' => [['dish_id' => $dish->id, 'quantity' => 1]]])
            ->assertNotFound();
        $this->get(route('public.menu', $owner->slug))->assertOk()->assertDontSee('id="cart-sheet"', false);

        // The orders already taken are still there for the owner.
        $this->actingAs($owner->user)->getJson(route('api.orders.index'))->assertOk();
    }

    public function test_switching_the_qr_studio_off_keeps_the_plain_code(): void
    {
        $owner = $this->owner(['hidden_sections' => ['qr'], 'qr_settings' => ['dot_style' => 'dots', 'dot_color' => '#7C3AED']]);

        $this->actingAs($owner->user)
            ->getJson(route('api.qr.show'))
            ->assertOk()
            ->assertJsonPath('data.unlocked', false)
            ->assertJsonPath('data.switched_off', true)
            ->assertJsonPath('data.settings.dot_style', 'square')
            ->assertJsonPath('data.card_url', null);

        $this->actingAs($owner->user)
            ->putJson(route('api.qr.update'), $owner->qrDefaultDesign())
            ->assertForbidden();

        // The saved design is kept for when the studio comes back.
        $this->assertSame('dots', $owner->fresh()->qr_settings['dot_style']);
    }

    public function test_a_guest_cannot_change_sections(): void
    {
        $this->putJson(route('api.sections.update'), ['hidden' => []])->assertUnauthorized();
    }

    public function test_switching_languages_off_makes_the_menu_english_only_without_forgetting(): void
    {
        $owner = $this->owner(['second_locale' => 'fr', 'default_locale' => 'fr', 'slug' => 'olive']);
        $owner->setTranslation('name', 'fr', 'Olivier')->save();

        $this->actingAs($owner->user)->putJson(route('api.sections.update'), ['hidden' => ['languages']])->assertOk();

        // The dashboard's forms get one language…
        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.languages', ['en'])
            ->assertJsonPath('data.restaurant.default_locale', 'en');

        // …but the session still says which second language was chosen.
        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.second_locale', 'fr');

        // Switching it back on brings the French menu straight back.
        $this->actingAs($owner->user)->putJson(route('api.sections.update'), ['hidden' => []])->assertOk();

        $restaurant = $owner->fresh();
        $this->assertSame(['en', 'fr'], $restaurant->menuLanguages());
        $this->assertSame('fr', \App\Services\Global\MenuLanguages::default($restaurant));
        $this->assertSame('Olivier', $restaurant->getTranslation('name', 'fr', false));
    }

    public function test_a_settings_save_while_languages_are_off_keeps_the_second_language(): void
    {
        $owner = $this->owner(['second_locale' => 'fr', 'default_locale' => 'fr', 'hidden_sections' => ['languages']]);

        // What the dashboard sends while the feature is off: no language fields.
        $this->actingAs($owner->user)
            ->putJson(route('api.settings.update'), ['name' => ['en' => 'Olive'], 'phone' => '+961 70 123 456', 'currency' => 'USD'])
            ->assertOk();

        $this->assertSame('fr', $owner->fresh()->second_locale);
        $this->assertSame('fr', $owner->fresh()->default_locale);
    }
}
