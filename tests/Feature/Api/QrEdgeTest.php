<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\Package;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class QrEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, mixed> */
    private function design(array $overrides = []): array
    {
        return array_merge([
            'dot_style' => 'dots', 'dot_color' => '#111418', 'dot_gradient' => null, 'gradient_type' => 'linear',
            'corner_style' => 'dot', 'corner_color' => '#111418', 'eye_style' => 'dot', 'eye_color' => '#111418',
            'background' => '#FFFFFF', 'logo' => false, 'logo_size' => 'medium', 'card_theme' => 'light',
            'title' => 'My Place', 'subtitle' => null, 'cta' => null, 'show_url' => true,
        ], $overrides);
    }

    private function lock(): void
    {
        Package::default()->setFeature(Feature::QrStudio, 0);
    }

    public function test_the_locked_payload_leaks_nothing_gated(): void
    {
        $this->lock();
        $owner = $this->owner();
        $owner->update(['qr_settings' => $this->design(['dot_style' => 'classy'])]);

        $data = $this->actingAs($owner->user)->getJson(route('api.qr.show'))->assertOk()->json('data');

        $this->assertFalse($data['unlocked']);
        $this->assertNull($data['stats']);
        $this->assertNull($data['card_url']);
        $this->assertNull($data['logo_data_url']);
        $this->assertSame('square', $data['settings']['dot_style'], 'The saved design is hidden while locked.');
    }

    public function test_the_encoded_link_never_changes_with_the_design(): void
    {
        $owner = $this->owner();
        $before = $this->actingAs($owner->user)->getJson(route('api.qr.show'))->json('data.url');

        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['dot_style' => 'classy-rounded']))->assertOk();

        $this->assertSame($before, $this->actingAs($owner->user)->getJson(route('api.qr.show'))->json('data.url'));
        $this->assertStringEndsWith('/'.$owner->slug.'?qr=1', $before);
    }

    public function test_every_choice_rejects_values_outside_its_set(): void
    {
        $owner = $this->owner();

        $bad = [
            'dot_style' => 'star', 'corner_style' => 'bevel', 'eye_style' => 'rounded',
            'gradient_type' => 'conic', 'logo_size' => 'huge', 'card_theme' => 'gold',
        ];

        foreach ($bad as $field => $value) {
            $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design([$field => $value]))
                ->assertStatus(422, $field)->assertJsonValidationErrors($field);
        }
    }

    public function test_every_shape_the_library_draws_is_accepted(): void
    {
        $owner = $this->owner();

        foreach (['square', 'dots', 'rounded', 'extra-rounded', 'classy', 'classy-rounded'] as $style) {
            $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['dot_style' => $style]))->assertOk();
        }

        foreach (['square', 'extra-rounded', 'dot'] as $style) {
            $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['corner_style' => $style]))->assertOk();
        }
    }

    public function test_colours_must_be_six_digit_hex(): void
    {
        $owner = $this->owner();

        foreach (['dot_color', 'corner_color', 'eye_color', 'background', 'dot_gradient'] as $field) {
            foreach (['#fff', 'red', '#ggg000', '123456'] as $bad) {
                $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design([$field => $bad]))
                    ->assertStatus(422, "{$field}={$bad}");
            }
        }
    }

    public function test_the_gradient_is_optional(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['dot_gradient' => null]))
            ->assertOk()->assertJsonPath('data.settings.dot_gradient', null);
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['dot_gradient' => '#7C3AED']))
            ->assertOk()->assertJsonPath('data.settings.dot_gradient', '#7C3AED');
    }

    public function test_text_fields_have_limits_and_may_be_empty(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['title' => str_repeat('x', 61)]))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['subtitle' => str_repeat('x', 81)]))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['cta' => str_repeat('x', 61)]))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['title' => null, 'subtitle' => '', 'cta' => null]))->assertOk();
    }

    public function test_the_logo_is_sent_inline_so_it_can_be_drawn_and_exported(): void
    {
        Storage::fake('public');
        $owner = $this->owner();
        $owner->addMedia(UploadedFile::fake()->image('logo.png', 40, 40))->toMediaCollection('logo');

        $logo = $this->actingAs($owner->user)->getJson(route('api.qr.show'))->assertOk()->json('data.logo_data_url');

        $this->assertStringStartsWith('data:image/png;base64,', $logo);
        $this->assertNotFalse(base64_decode(substr($logo, strlen('data:image/png;base64,')), true));
    }

    public function test_choosing_the_logo_without_one_uploaded_draws_no_image(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['logo' => true]))
            ->assertOk()->assertJsonPath('data.logo_data_url', null);

        $this->get(route('public.qr', $owner->slug))->assertOk()->assertDontSee('"image":', false);
    }

    public function test_keys_from_an_older_design_are_ignored(): void
    {
        // A design saved under names this version no longer uses must not
        // reach the card or the dashboard.
        $owner = $this->owner();
        $owner->update(['qr_settings' => ['bg' => 'gold', 'dot' => '#a8863c', 'dot_style' => 'rounded']]);

        $settings = $owner->fresh()->qrDesign();

        $this->assertArrayNotHasKey('bg', $settings);
        $this->assertArrayNotHasKey('dot', $settings);
        $this->assertSame('rounded', $settings['dot_style'], 'A key that still exists keeps its saved value.');
    }

    public function test_the_public_card_reflects_the_saved_design_and_hides_when_locked(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['title' => 'Card Name']))->assertOk();

        $this->get(route('public.qr', $owner->slug))->assertOk()->assertSee('Card Name');

        $this->lock();

        $this->get(route('public.qr', $owner->slug))->assertNotFound();
    }

    public function test_a_brand_card_takes_the_menus_colour(): void
    {
        $owner = $this->published(['template_settings' => ['primary_color' => '#EA4335']]);
        $owner->update(['qr_settings' => $this->design(['card_theme' => 'brand'])]);

        $this->get(route('public.qr', $owner->slug))
            ->assertOk()
            ->assertSee('--accent: #EA4335', false)
            ->assertSee('theme-brand', false);

        // The dashboard is told the same colour, to show it in the picker.
        $this->actingAs($owner->user)->getJson(route('api.qr.show'))->assertJsonPath('data.brand_color', '#EA4335');
    }

    public function test_without_a_template_the_brand_colour_is_the_classic_blue(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->getJson(route('api.qr.show'))->assertJsonPath('data.brand_color', '#1F6FEB');
    }

    public function test_the_card_is_not_found_for_an_unknown_or_reserved_slug(): void
    {
        $this->get('/no-such-place/qr')->assertNotFound();
        $this->get('/admin/qr')->assertNotFound();
    }

    public function test_stats_are_scoped_to_the_owner(): void
    {
        $owner = $this->owner();
        $other = $this->published();
        $other->statistics()->create(['session_id' => 'x', 'via_qr' => true, 'viewed_at' => now()]);

        $this->actingAs($owner->user)->getJson(route('api.qr.show'))->assertJsonPath('data.stats.total', 0);
    }

    public function test_the_default_title_is_the_restaurants_name(): void
    {
        $owner = $this->owner(['name' => ['en' => 'Olive']]);

        $this->assertSame('Olive', Restaurant::find($owner->id)->qrDesign()['title']);
    }
}
