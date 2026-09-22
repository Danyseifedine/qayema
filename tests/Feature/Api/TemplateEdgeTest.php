<?php

namespace Tests\Feature\Api;

use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class TemplateEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_unlocking_with_exactly_enough_coins_works_and_leaves_zero(): void
    {
        $owner = $this->ownerWithCoins(650);
        $template = Template::factory()->paid(650)->create();

        $this->actingAs($owner->user)->postJson(route('api.templates.unlock'), ['template_id' => $template->id])->assertOk()->assertJsonPath('meta.balance', 0);
    }

    public function test_one_coin_short_is_refused(): void
    {
        $owner = $this->ownerWithCoins(649);
        $template = Template::factory()->paid(650)->create();

        $this->actingAs($owner->user)->postJson(route('api.templates.unlock'), ['template_id' => $template->id])->assertStatus(402)->assertJsonPath('shortfall', 1);
    }

    public function test_a_template_deactivated_after_selection_keeps_the_owner_on_it_until_they_switch(): void
    {
        $template = Template::factory()->create();
        $owner = $this->owner(['template_id' => $template->id]);
        $template->update(['is_active' => false]);

        // The listing hides it, the current pointer still reports it, and the
        // owner can move to any active template.
        $response = $this->actingAs($owner->user)->getJson(route('api.templates.index'))->assertOk();
        $this->assertNotContains($template->id, array_column($response->json('data'), 'id'));
        $response->assertJsonPath('meta.current', $template->id);

        $other = Template::factory()->create();
        $this->actingAs($owner->user)->postJson(route('api.templates.select'), ['template_id' => $other->id])->assertOk();
    }

    public function test_boolean_settings_accept_string_spellings(): void
    {
        $template = Template::factory()->withSettings([['key' => 'show_prices', 'type' => 'boolean', 'default' => true]])->create();
        $owner = $this->owner(['template_id' => $template->id, 'template_settings' => $template->defaultSettings()]);

        $this->actingAs($owner->user)->putJson(route('api.template-settings.update'), ['settings' => ['show_prices' => '0']])->assertOk()->assertJsonPath('data.settings.show_prices', '0');
        $this->actingAs($owner->user)->putJson(route('api.template-settings.update'), ['settings' => ['show_prices' => 'nope']])->assertStatus(422);
    }

    public function test_a_choice_removed_from_the_schema_is_rejected_on_the_next_save(): void
    {
        $template = Template::factory()->withSettings([['key' => 'density', 'type' => 'select', 'default' => 'cosy', 'options' => ['cosy', 'compact']]])->create();
        $owner = $this->owner(['template_id' => $template->id, 'template_settings' => ['density' => 'compact']]);

        $template->update(['settings_schema' => [['key' => 'density', 'type' => 'select', 'default' => 'cosy', 'options' => ['cosy']]]]);

        $this->actingAs($owner->user)->putJson(route('api.template-settings.update'), ['settings' => ['density' => 'compact']])->assertStatus(422);
    }

    public function test_a_setting_added_to_the_schema_later_shows_up_with_its_default(): void
    {
        $template = Template::factory()->withSettings([['key' => 'primary', 'type' => 'color', 'default' => '#111111']])->create();
        $owner = $this->owner(['template_id' => $template->id, 'template_settings' => ['primary' => '#222222']]);

        $template->update(['settings_schema' => [
            ['key' => 'primary', 'type' => 'color', 'default' => '#111111'],
            ['key' => 'accent', 'type' => 'color', 'default' => '#ABCDEF'],
        ]]);

        $this->actingAs($owner->user)->putJson(route('api.template-settings.update'), ['settings' => ['primary' => '#222222']])
            ->assertOk()->assertJsonPath('data.settings.accent', '#ABCDEF')->assertJsonPath('data.settings.primary', '#222222');
    }

    public function test_text_settings_have_a_length_limit(): void
    {
        $template = Template::factory()->withSettings([['key' => 'heading', 'type' => 'text', 'default' => 'Menu']])->create();
        $owner = $this->owner(['template_id' => $template->id]);

        $this->actingAs($owner->user)->putJson(route('api.template-settings.update'), ['settings' => ['heading' => str_repeat('x', 256)]])->assertStatus(422);
    }

    public function test_colours_are_case_insensitive_hex_only(): void
    {
        $template = Template::factory()->withSettings([['key' => 'primary', 'type' => 'color', 'default' => '#111111']])->create();
        $owner = $this->owner(['template_id' => $template->id]);

        $this->actingAs($owner->user)->putJson(route('api.template-settings.update'), ['settings' => ['primary' => '#AbCdEf']])->assertOk();
        foreach (['#FFF', 'FFFFFF', '#GGGGGG', 'rgb(0,0,0)', '#12345678'] as $bad) {
            $this->actingAs($owner->user)->putJson(route('api.template-settings.update'), ['settings' => ['primary' => $bad]])->assertStatus(422, $bad);
        }
    }

    public function test_the_listing_is_ordered_by_sort_order_then_id(): void
    {
        $owner = $this->owner();
        $second = Template::factory()->create(['slug' => 'b', 'sort_order' => 1]);
        $first = Template::factory()->create(['slug' => 'a', 'sort_order' => 0]);

        $slugs = array_column($this->actingAs($owner->user)->getJson(route('api.templates.index'))->json('data'), 'slug');

        $this->assertSame(['a', 'b'], $slugs);
    }
}
