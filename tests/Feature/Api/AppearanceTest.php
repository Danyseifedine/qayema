<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class AppearanceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::Appearance, Feature::MultipleLanguages);
    }

    /** Classic's knobs (colours and the name switch) plus a text and a choice. */
    private function design(array $extra = []): Template
    {
        return Template::factory()->withSettings(array_merge(Template::CLASSIC_SCHEMA, [
            ['key' => 'heading', 'type' => 'text', 'default' => 'Our Menu'],
            ['key' => 'density', 'type' => 'select', 'default' => 'cosy', 'options' => ['cosy', 'compact']],
        ], $extra))->create();
    }

    private function ownerOnDesign(?Template $design = null, array $restaurant = []): Restaurant
    {
        return $this->owner(array_merge(['template_id' => ($design ?? $this->design())->id], $restaurant));
    }

    /**
     * Signed in as the owner, reloaded: a request caches the restaurant and
     * its design on the user, which would hide a switch made since.
     */
    private function as(Restaurant $owner): static
    {
        return $this->actingAs($owner->user->fresh());
    }

    public function test_it_needs_a_signed_in_owner_with_a_design(): void
    {
        $this->getJson(route('api.appearance.show'))->assertUnauthorized();
        $this->putJson(route('api.appearance.update'), [])->assertUnauthorized();

        $owner = $this->owner(['template_id' => null]);
        $this->as($owner)->getJson(route('api.appearance.show'))->assertForbidden();
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => []])->assertForbidden();
    }

    public function test_it_lists_every_setting_the_design_declares_with_its_type(): void
    {
        $owner = $this->ownerOnDesign();

        $colors = $this->as($owner)->getJson(route('api.appearance.show'))->assertOk()->json('data.settings');

        $this->assertSame(['primary_color', 'background_color', 'text_color', 'show_name', 'heading', 'density'], array_column($colors, 'key'));
        $this->assertSame(['color', 'color', 'color', 'boolean', 'text', 'select'], array_column($colors, 'type'));
        $this->assertTrue($colors[3]['value']);
        $this->assertSame(['cosy', 'compact'], $colors[5]['options']);
        $this->assertSame(['en' => 'Main colour', 'ar' => 'اللون الرئيسي'], $colors[0]['label']);
        $this->assertSame(Template::DEFAULT_PRIMARY_COLOR, $colors[0]['value']);
        $this->assertSame('background_color', $colors[2]['contrast_with']);
    }

    public function test_a_design_without_settings_lists_none(): void
    {
        $owner = $this->ownerOnDesign(Template::factory()->create(['settings_schema' => null]));

        $this->as($owner)->getJson(route('api.appearance.show'))
            ->assertOk()
            ->assertJsonPath('data.settings', []);
    }

    public function test_an_owner_can_change_the_colours_and_only_what_was_sent_changes(): void
    {
        $owner = $this->ownerOnDesign();

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['background_color' => '#FAF7F0']])->assertOk();
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => '#C0392B']])
            ->assertOk()
            ->assertJsonPath('data.settings.0.value', '#C0392B')
            ->assertJsonPath('data.settings.1.value', '#FAF7F0')
            ->assertJsonPath('data.settings.2.value', '#111418');

        // Only the owner's choices are stored, so a design default the admin
        // changes later still reaches the colours they never touched.
        $this->assertEquals(
            ['primary_color' => '#C0392B', 'background_color' => '#FAF7F0'],
            $owner->fresh()->chosenDesignSettings($owner->template),
        );
    }

    public function test_null_puts_a_colour_back_to_the_design_default(): void
    {
        $owner = $this->ownerOnDesign();
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => '#C0392B']])->assertOk();

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => null]])
            ->assertOk()
            ->assertJsonPath('data.settings.0.value', Template::DEFAULT_PRIMARY_COLOR);
    }

    public function test_colours_are_six_digit_hex_in_either_case(): void
    {
        $owner = $this->ownerOnDesign();

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => '#AbCdEf']])->assertOk();

        foreach (['red', '#FFF', 'FFFFFF', '#GGGGGG', 'rgb(0,0,0)', '#12345678', '#fff;}body{x:y'] as $bad) {
            $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => $bad]])
                ->assertStatus(422, $bad)
                ->assertJsonValidationErrors('settings.primary_color');
        }
    }

    public function test_a_key_the_design_does_not_declare_is_rejected(): void
    {
        $owner = $this->ownerOnDesign();

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['evil_key' => '#112233']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('settings.evil_key');

        $this->assertSame([], $owner->fresh()->chosenDesignSettings($owner->template));
    }

    public function test_an_on_off_setting_is_stored_as_a_real_boolean(): void
    {
        $owner = $this->ownerOnDesign();

        foreach ([false, 0, '0'] as $off) {
            $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['show_name' => $off]])
                ->assertOk()
                ->assertJsonPath('data.settings.3.value', false);
            $this->assertFalse($owner->fresh()->chosenDesignSettings($owner->template)['show_name']);
        }

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['show_name' => 'nope']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('settings.show_name');
    }

    public function test_a_choice_must_be_one_of_its_options_and_text_is_trimmed(): void
    {
        $owner = $this->ownerOnDesign();

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['density' => 'enormous']])
            ->assertStatus(422)
            ->assertJsonValidationErrors('settings.density');
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['heading' => str_repeat('x', 256)]])
            ->assertStatus(422);

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['density' => 'compact', 'heading' => '  Tonight  ']])
            ->assertOk()
            ->assertJsonPath('data.settings.4.value', 'Tonight')
            ->assertJsonPath('data.settings.5.value', 'compact');

        // Blank text goes back to the design's own.
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['heading' => '   ']])
            ->assertOk()
            ->assertJsonPath('data.settings.4.value', 'Our Menu');
    }

    public function test_a_choice_removed_from_the_design_later_falls_back_to_the_default(): void
    {
        $design = Template::factory()->withSettings([['key' => 'density', 'type' => 'select', 'default' => 'cosy', 'options' => ['cosy', 'compact']]])->create();
        $owner = $this->ownerOnDesign($design);
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['density' => 'compact']])->assertOk();

        $design->update(['settings_schema' => [['key' => 'density', 'type' => 'select', 'default' => 'cosy', 'options' => ['cosy']]]]);

        $this->as($owner)->getJson(route('api.appearance.show'))->assertJsonPath('data.settings.0.value', 'cosy');
    }

    public function test_a_colour_added_to_the_design_later_shows_its_default(): void
    {
        $design = Template::factory()->withSettings([['key' => 'primary', 'type' => 'color', 'default' => '#111111']])->create();
        $owner = $this->ownerOnDesign($design);
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary' => '#222222']])->assertOk();

        $design->update(['settings_schema' => [
            ['key' => 'primary', 'type' => 'color', 'default' => '#111111'],
            ['key' => 'accent', 'type' => 'color', 'default' => '#ABCDEF'],
        ]]);

        $this->as($owner)->getJson(route('api.appearance.show'))
            ->assertJsonPath('data.settings.0.value', '#222222')
            ->assertJsonPath('data.settings.1.value', '#ABCDEF');
    }

    public function test_each_design_remembers_its_own_colours(): void
    {
        $classic = $this->design();
        $midnight = $this->design();
        $owner = $this->ownerOnDesign($classic);

        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => '#C0392B']])->assertOk();

        // A new design starts from its own defaults…
        $this->as($owner)->postJson(route('api.templates.select'), ['template_id' => $midnight->id])->assertOk();
        $this->as($owner)->getJson(route('api.appearance.show'))->assertJsonPath('data.settings.0.value', Template::DEFAULT_PRIMARY_COLOR);
        $this->as($owner)->putJson(route('api.appearance.update'), ['settings' => ['primary_color' => '#1F6FEB']])->assertOk();

        // …and coming back brings the first one's colours back.
        $this->as($owner)->postJson(route('api.templates.select'), ['template_id' => $classic->id])->assertOk();
        $this->as($owner)->getJson(route('api.appearance.show'))->assertJsonPath('data.settings.0.value', '#C0392B');
    }

    public function test_one_font_picker_per_writing_system(): void
    {
        // English + Spanish share Latin: one picker.
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'es']);
        $fonts = $this->as($owner)->getJson(route('api.appearance.show'))->json('data.fonts');
        $this->assertSame(['latin'], array_column($fonts, 'script'));
        $this->assertSame(['en', 'es'], $fonts[0]['languages']);
        $this->assertSame('Inter', $fonts[0]['value']);

        // English + Arabic: two.
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'ar']);
        $fonts = $this->as($owner)->getJson(route('api.appearance.show'))->json('data.fonts');
        $this->assertSame(['latin', 'arabic'], array_column($fonts, 'script'));
        $this->assertSame('El Messiri', $fonts[1]['value']);
        $this->assertContains('Cairo', array_column($fonts[1]['options'], 'family'));
    }

    public function test_an_owner_can_pick_a_font_per_writing_system(): void
    {
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'ar']);

        $this->as($owner)->putJson(route('api.appearance.update'), ['fonts' => ['latin' => 'Poppins', 'arabic' => 'Cairo']])
            ->assertOk()
            ->assertJsonPath('data.fonts.0.value', 'Poppins')
            ->assertJsonPath('data.fonts.1.value', 'Cairo');

        $this->assertSame(['latin' => 'Poppins', 'arabic' => 'Cairo'], $owner->fresh()->menu_fonts);

        // Null puts one back to its default and leaves the other.
        $this->as($owner)->putJson(route('api.appearance.update'), ['fonts' => ['arabic' => null]])
            ->assertJsonPath('data.fonts.0.value', 'Poppins')
            ->assertJsonPath('data.fonts.1.value', 'El Messiri');
    }

    public function test_a_font_must_be_on_its_scripts_list_and_the_script_on_the_menu(): void
    {
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'es']);

        // Cairo is an Arabic font; Poppins has no Cyrillic; Arabic is not on this menu.
        foreach ([['latin' => 'Cairo'], ['latin' => 'Comic Sans MS'], ['arabic' => 'Cairo']] as $fonts) {
            $this->as($owner)->putJson(route('api.appearance.update'), ['fonts' => $fonts])
                ->assertStatus(422)
                ->assertJsonValidationErrors('fonts.'.array_key_first($fonts));
        }

        $this->assertNull($owner->fresh()->menu_fonts);
    }

    public function test_switching_languages_off_hides_a_scripts_picker_but_keeps_the_choice(): void
    {
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'ar', 'menu_fonts' => ['arabic' => 'Cairo']]);
        $owner->update(['switched_off' => ['languages']]);

        $this->as($owner)->getJson(route('api.appearance.show'))
            ->assertJsonCount(1, 'data.fonts')
            ->assertJsonPath('data.fonts.0.script', 'latin');

        $owner->update(['switched_off' => []]);
        $this->as($owner)->getJson(route('api.appearance.show'))
            ->assertJsonPath('data.fonts.1.value', 'Cairo');
    }
}
