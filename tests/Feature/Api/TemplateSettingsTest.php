<?php

namespace Tests\Feature\Api;

use App\Models\Restaurant;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateSettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A restaurant on a template that exposes a colour, a label and a choice.
     *
     * @return array{0: Restaurant, 1: Template}
     */
    private function onCustomizableTemplate(): array
    {
        $template = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#C8A85A'],
            ['key' => 'heading', 'type' => 'text', 'default' => 'Our Menu'],
            ['key' => 'show_prices', 'type' => 'boolean', 'default' => true],
            ['key' => 'density', 'type' => 'select', 'default' => 'cosy', 'options' => ['cosy', 'compact']],
        ])->create();

        $restaurant = Restaurant::factory()->create([
            'template_id' => $template->id,
            'template_settings' => $template->defaultSettings(),
        ]);

        return [$restaurant, $template];
    }

    public function test_settings_require_authentication(): void
    {
        $this->putJson(route('api.template-settings.update'), ['settings' => []])->assertUnauthorized();
    }

    public function test_an_owner_can_change_their_templates_colours(): void
    {
        [$restaurant] = $this->onCustomizableTemplate();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), [
                'settings' => ['primary_color' => '#112233', 'heading' => 'Menu'],
            ])
            ->assertOk()
            ->assertJsonPath('data.settings.primary_color', '#112233')
            ->assertJsonPath('data.settings.heading', 'Menu');

        $this->assertSame('#112233', $restaurant->fresh()->template_settings['primary_color']);
    }

    public function test_omitted_settings_keep_the_template_default(): void
    {
        [$restaurant] = $this->onCustomizableTemplate();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), [
                'settings' => ['primary_color' => '#000000'],
            ])
            ->assertOk()
            // Untouched keys fall back to the schema's defaults rather than
            // vanishing from the stored settings.
            ->assertJsonPath('data.settings.heading', 'Our Menu')
            ->assertJsonPath('data.settings.density', 'cosy');
    }

    public function test_an_invalid_colour_is_rejected(): void
    {
        [$restaurant] = $this->onCustomizableTemplate();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), [
                'settings' => ['primary_color' => 'red'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('settings.primary_color');
    }

    public function test_a_choice_outside_the_declared_options_is_rejected(): void
    {
        [$restaurant] = $this->onCustomizableTemplate();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), [
                'settings' => ['density' => 'enormous'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('settings.density');
    }

    public function test_a_key_the_template_does_not_declare_is_rejected(): void
    {
        [$restaurant] = $this->onCustomizableTemplate();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), [
                'settings' => ['evil_key' => 'anything'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('settings.evil_key');

        $this->assertArrayNotHasKey('evil_key', $restaurant->fresh()->template_settings);
    }

    public function test_a_template_with_no_schema_accepts_no_customization(): void
    {
        $template = Template::factory()->create(['settings_schema' => null]);
        $restaurant = Restaurant::factory()->create(['template_id' => $template->id]);

        // A fixed design: every key is unknown, so nothing can be changed.
        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), [
                'settings' => ['primary_color' => '#112233'],
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('settings.primary_color');
    }

    public function test_an_owner_without_a_template_cannot_save_settings(): void
    {
        $restaurant = Restaurant::factory()->create(['template_id' => null]);

        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), ['settings' => []])
            ->assertForbidden();
    }

    public function test_switching_templates_resets_settings_to_the_new_defaults(): void
    {
        [$restaurant] = $this->onCustomizableTemplate();

        $this->actingAs($restaurant->user)
            ->putJson(route('api.template-settings.update'), ['settings' => ['primary_color' => '#000000']])
            ->assertOk();

        $other = Template::factory()->withSettings([
            ['key' => 'primary_color', 'type' => 'color', 'default' => '#FFFFFF'],
        ])->create();

        $this->actingAs($restaurant->user)
            ->postJson(route('api.templates.select'), ['template_id' => $other->id])
            ->assertOk()
            ->assertJsonPath('meta.settings.primary_color', '#FFFFFF');
    }
}
