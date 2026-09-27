<?php

namespace Tests\Feature\Api;

use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class ColorsFontsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** A design with the classic knobs plus a non-colour one the page ignores. */
    private function design(array $extra = []): Template
    {
        return Template::factory()->withSettings(array_merge(Template::CLASSIC_SCHEMA, [
            ['key' => 'heading', 'type' => 'text', 'default' => 'Our Menu'],
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
        $this->getJson(route('api.colors-fonts.show'))->assertUnauthorized();
        $this->putJson(route('api.colors-fonts.update'), [])->assertUnauthorized();

        $owner = $this->owner(['template_id' => null]);
        $this->as($owner)->getJson(route('api.colors-fonts.show'))->assertForbidden();
        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => []])->assertForbidden();
    }

    public function test_it_lists_the_colours_the_design_declares_and_nothing_else(): void
    {
        $owner = $this->ownerOnDesign();

        $colors = $this->as($owner)->getJson(route('api.colors-fonts.show'))->assertOk()->json('data.colors');

        $this->assertSame(['primary_color', 'background_color', 'text_color'], array_column($colors, 'key'));
        $this->assertSame(['en' => 'Main colour', 'ar' => 'اللون الرئيسي'], $colors[0]['label']);
        $this->assertSame(Template::DEFAULT_PRIMARY_COLOR, $colors[0]['value']);
        $this->assertSame('background_color', $colors[2]['contrast_with']);
    }

    public function test_a_design_without_colours_lists_none(): void
    {
        $owner = $this->ownerOnDesign(Template::factory()->create(['settings_schema' => null]));

        $this->as($owner)->getJson(route('api.colors-fonts.show'))
            ->assertOk()
            ->assertJsonPath('data.colors', []);
    }

    public function test_an_owner_can_change_the_colours_and_only_what_was_sent_changes(): void
    {
        $owner = $this->ownerOnDesign();

        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['background_color' => '#FAF7F0']])->assertOk();
        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => '#C0392B']])
            ->assertOk()
            ->assertJsonPath('data.colors.0.value', '#C0392B')
            ->assertJsonPath('data.colors.1.value', '#FAF7F0')
            ->assertJsonPath('data.colors.2.value', '#111418');

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
        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => '#C0392B']])->assertOk();

        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => null]])
            ->assertOk()
            ->assertJsonPath('data.colors.0.value', Template::DEFAULT_PRIMARY_COLOR);
    }

    public function test_colours_are_six_digit_hex_in_either_case(): void
    {
        $owner = $this->ownerOnDesign();

        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => '#AbCdEf']])->assertOk();

        foreach (['red', '#FFF', 'FFFFFF', '#GGGGGG', 'rgb(0,0,0)', '#12345678', '#fff;}body{x:y'] as $bad) {
            $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => $bad]])
                ->assertStatus(422, $bad)
                ->assertJsonValidationErrors('colors.primary_color');
        }
    }

    public function test_a_key_the_design_does_not_declare_as_a_colour_is_rejected(): void
    {
        $owner = $this->ownerOnDesign();

        // Undeclared, and declared but not a colour: neither is editable here.
        foreach (['evil_key' => '#112233', 'heading' => '#112233'] as $key => $value) {
            $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => [$key => $value]])
                ->assertStatus(422)
                ->assertJsonValidationErrors("colors.{$key}");
        }

        $this->assertSame([], $owner->fresh()->chosenDesignSettings($owner->template));
    }

    public function test_a_colour_added_to_the_design_later_shows_its_default(): void
    {
        $design = Template::factory()->withSettings([['key' => 'primary', 'type' => 'color', 'default' => '#111111']])->create();
        $owner = $this->ownerOnDesign($design);
        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary' => '#222222']])->assertOk();

        $design->update(['settings_schema' => [
            ['key' => 'primary', 'type' => 'color', 'default' => '#111111'],
            ['key' => 'accent', 'type' => 'color', 'default' => '#ABCDEF'],
        ]]);

        $this->as($owner)->getJson(route('api.colors-fonts.show'))
            ->assertJsonPath('data.colors.0.value', '#222222')
            ->assertJsonPath('data.colors.1.value', '#ABCDEF');
    }

    public function test_each_design_remembers_its_own_colours(): void
    {
        $classic = $this->design();
        $midnight = $this->design();
        $owner = $this->ownerOnDesign($classic);

        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => '#C0392B']])->assertOk();

        // A new design starts from its own defaults…
        $this->as($owner)->postJson(route('api.templates.select'), ['template_id' => $midnight->id])->assertOk();
        $this->as($owner)->getJson(route('api.colors-fonts.show'))->assertJsonPath('data.colors.0.value', Template::DEFAULT_PRIMARY_COLOR);
        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['primary_color' => '#1F6FEB']])->assertOk();

        // …and coming back brings the first one's colours back.
        $this->as($owner)->postJson(route('api.templates.select'), ['template_id' => $classic->id])->assertOk();
        $this->as($owner)->getJson(route('api.colors-fonts.show'))->assertJsonPath('data.colors.0.value', '#C0392B');
    }

    public function test_colours_saved_before_designs_kept_their_own_still_count(): void
    {
        // The old shape: one flat map for the design in use.
        $owner = $this->ownerOnDesign(null, ['template_settings' => ['primary_color' => '#C0392B']]);

        $this->as($owner)->getJson(route('api.colors-fonts.show'))->assertJsonPath('data.colors.0.value', '#C0392B');

        // The first save moves it under the design, keeping it.
        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['colors' => ['text_color' => '#222222']])->assertOk();
        $this->assertEquals(
            [$owner->template_id => ['primary_color' => '#C0392B', 'text_color' => '#222222']],
            $owner->fresh()->template_settings,
        );
    }

    public function test_one_font_picker_per_writing_system(): void
    {
        // English + Spanish share Latin: one picker.
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'es']);
        $fonts = $this->as($owner)->getJson(route('api.colors-fonts.show'))->json('data.fonts');
        $this->assertSame(['latin'], array_column($fonts, 'script'));
        $this->assertSame(['en', 'es'], $fonts[0]['languages']);
        $this->assertSame('Inter', $fonts[0]['value']);

        // English + Arabic: two.
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'ar']);
        $fonts = $this->as($owner)->getJson(route('api.colors-fonts.show'))->json('data.fonts');
        $this->assertSame(['latin', 'arabic'], array_column($fonts, 'script'));
        $this->assertSame('El Messiri', $fonts[1]['value']);
        $this->assertContains('Cairo', array_column($fonts[1]['options'], 'family'));
    }

    public function test_an_owner_can_pick_a_font_per_writing_system(): void
    {
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'ar']);

        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['fonts' => ['latin' => 'Poppins', 'arabic' => 'Cairo']])
            ->assertOk()
            ->assertJsonPath('data.fonts.0.value', 'Poppins')
            ->assertJsonPath('data.fonts.1.value', 'Cairo');

        $this->assertSame(['latin' => 'Poppins', 'arabic' => 'Cairo'], $owner->fresh()->menu_fonts);

        // Null puts one back to its default and leaves the other.
        $this->as($owner)->putJson(route('api.colors-fonts.update'), ['fonts' => ['arabic' => null]])
            ->assertJsonPath('data.fonts.0.value', 'Poppins')
            ->assertJsonPath('data.fonts.1.value', 'El Messiri');
    }

    public function test_a_font_must_be_on_its_scripts_list_and_the_script_on_the_menu(): void
    {
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'es']);

        // Cairo is an Arabic font; Poppins has no Cyrillic; Arabic is not on this menu.
        foreach ([['latin' => 'Cairo'], ['latin' => 'Comic Sans MS'], ['arabic' => 'Cairo']] as $fonts) {
            $this->as($owner)->putJson(route('api.colors-fonts.update'), ['fonts' => $fonts])
                ->assertStatus(422)
                ->assertJsonValidationErrors('fonts.'.array_key_first($fonts));
        }

        $this->assertNull($owner->fresh()->menu_fonts);
    }

    public function test_switching_languages_off_hides_a_scripts_picker_but_keeps_the_choice(): void
    {
        $owner = $this->ownerOnDesign(null, ['second_locale' => 'ar', 'menu_fonts' => ['arabic' => 'Cairo']]);
        $owner->update(['switched_off' => ['languages']]);

        $this->as($owner)->getJson(route('api.colors-fonts.show'))
            ->assertJsonCount(1, 'data.fonts')
            ->assertJsonPath('data.fonts.0.script', 'latin');

        $owner->update(['switched_off' => []]);
        $this->as($owner)->getJson(route('api.colors-fonts.show'))
            ->assertJsonPath('data.fonts.1.value', 'Cairo');
    }
}
