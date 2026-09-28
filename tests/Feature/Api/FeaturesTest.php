<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class FeaturesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    public function test_every_feature_is_on_until_the_owner_switches_one_off(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.switched_off', []);
    }

    public function test_an_owner_can_switch_features_off_and_on(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->putJson(route('api.features.update'), ['off' => ['orders', 'analytics']])
            ->assertOk()
            ->assertJsonPath('data.off', ['orders', 'analytics']);

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.switched_off', ['orders', 'analytics']);

        $this->actingAs($owner->user)
            ->putJson(route('api.features.update'), ['off' => []])
            ->assertOk()
            ->assertJsonPath('data.off', []);
    }

    public function test_only_optional_features_can_be_switched_off(): void
    {
        $owner = $this->owner();

        foreach (['overview', 'dishes', 'settings', 'package', 'social-links', 'nonsense'] as $feature) {
            $this->actingAs($owner->user)
                ->putJson(route('api.features.update'), ['off' => [$feature]])
                ->assertStatus(422, $feature)
                ->assertJsonValidationErrors('off.0');
        }
    }

    public function test_the_list_must_be_sent(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->putJson(route('api.features.update'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('off');
    }

    public function test_switching_orders_off_stops_the_menu_taking_them(): void
    {
        $this->defaultPackageSets(\App\Enums\Feature::Ordering, 1);
        $owner = $this->published(['slug' => 'olive', 'switched_off' => ['orders']]);
        $dish = \App\Models\Dish::factory()->for($owner)->create(['price' => '5.00']);

        $this->postJson(route('public.order', $owner->slug), ['items' => [['dish_id' => $dish->id, 'quantity' => 1]]])
            ->assertNotFound();
        $this->get(route('public.menu', $owner->slug))->assertOk()->assertDontSee('id="cart-sheet"', false);

        // The orders already taken are still there for the owner.
        $this->actingAs($owner->user)->getJson(route('api.orders.index'))->assertOk();
    }

    public function test_switching_the_qr_studio_off_keeps_the_plain_code(): void
    {
        $owner = $this->owner(['switched_off' => ['qr'], 'qr_settings' => ['dot_style' => 'dots', 'dot_color' => '#7C3AED']]);

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

    public function test_a_guest_cannot_change_features(): void
    {
        $this->putJson(route('api.features.update'), ['off' => []])->assertUnauthorized();
    }

    public function test_switching_languages_off_makes_the_menu_english_only_without_forgetting(): void
    {
        $owner = $this->owner(['second_locale' => 'fr', 'default_locale' => 'fr', 'slug' => 'olive']);
        $owner->setTranslation('name', 'fr', 'Olivier')->save();

        $this->actingAs($owner->user)->putJson(route('api.features.update'), ['off' => ['languages']])->assertOk();

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
        $this->actingAs($owner->user)->putJson(route('api.features.update'), ['off' => []])->assertOk();

        $restaurant = $owner->fresh();
        $this->assertSame(['en', 'fr'], $restaurant->menuLanguages());
        $this->assertSame('fr', \App\Services\Menu\MenuLanguages::default($restaurant));
        $this->assertSame('Olivier', $restaurant->getTranslation('name', 'fr', false));
    }

    public function test_a_settings_save_while_languages_are_off_keeps_the_second_language(): void
    {
        $owner = $this->owner(['second_locale' => 'fr', 'default_locale' => 'fr', 'switched_off' => ['languages']]);

        // What the dashboard sends while the feature is off: no language fields.
        $this->actingAs($owner->user)
            ->patchJson(route('api.restaurant.update'), ['name' => ['en' => 'Olive'], 'phone' => '+961 70 123 456', 'currency' => 'USD'])
            ->assertOk();

        $this->assertSame('fr', $owner->fresh()->second_locale);
        $this->assertSame('fr', $owner->fresh()->default_locale);
    }
}
