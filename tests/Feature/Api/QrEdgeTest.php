<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\RestaurantFeature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class QrEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function design(array $overrides = []): array
    {
        return array_merge([
            'bg' => 'ink', 'dot' => '#a8863c', 'eye' => '#3c8c66', 'dot_style' => 'rounded',
            'corner' => 'pill', 'logo' => 'none', 'show_url' => true, 'name' => 'My Place', 'tagline' => null, 'cta' => null,
        ], $overrides);
    }

    private function unlocked(): \App\Models\Restaurant
    {
        $owner = $this->owner();
        RestaurantFeature::factory()->for($owner)->forFeature(Feature::QrStudio)->create();

        return $owner;
    }

    public function test_the_locked_payload_leaks_nothing_premium(): void
    {
        $owner = $this->owner();
        $owner->update(['qr_settings' => $this->design(['bg' => 'gold'])]);

        $data = $this->actingAs($owner->user)->getJson(route('api.qr.show'))->assertOk()->json('data');

        $this->assertFalse($data['unlocked']);
        $this->assertNull($data['stats']);
        $this->assertNull($data['card_url']);
        $this->assertNull($data['logo_url']);
        $this->assertSame('cream', $data['settings']['bg'], 'Saved premium design is hidden while locked.');
    }

    public function test_the_encoded_link_never_changes_with_the_design(): void
    {
        $owner = $this->unlocked();
        $before = $this->actingAs($owner->user)->getJson(route('api.qr.show'))->json('data.url');

        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['bg' => 'olive']))->assertOk();

        $this->assertSame($before, $this->actingAs($owner->user)->getJson(route('api.qr.show'))->json('data.url'));
        $this->assertStringEndsWith('/'.$owner->slug.'?qr=1', $before);
    }

    public function test_every_enum_field_rejects_values_outside_its_set(): void
    {
        $owner = $this->unlocked();

        foreach (['bg' => 'neon', 'dot_style' => 'star', 'corner' => 'bevel', 'logo' => 'mark'] as $field => $bad) {
            $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design([$field => $bad]))
                ->assertStatus(422, $field)->assertJsonValidationErrors($field);
        }
    }

    public function test_colours_must_be_six_digit_hex(): void
    {
        $owner = $this->unlocked();

        foreach (['#fff', 'red', '#ggg000', '123456'] as $bad) {
            $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['dot' => $bad]))->assertStatus(422, $bad);
        }
    }

    public function test_text_fields_have_limits_and_may_be_empty(): void
    {
        $owner = $this->unlocked();

        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['name' => str_repeat('x', 61)]))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['tagline' => str_repeat('x', 81)]))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['cta' => str_repeat('x', 61)]))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['name' => null, 'tagline' => '', 'cta' => null]))->assertOk();
    }

    public function test_choosing_the_image_logo_without_a_logo_uploaded_is_allowed_but_renders_nothing(): void
    {
        $owner = $this->unlocked();

        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['logo' => 'image']))->assertOk()->assertJsonPath('data.logo_url', null);
    }

    public function test_the_public_card_reflects_the_saved_design_and_hides_when_locked_again(): void
    {
        $owner = $this->unlocked();
        $this->actingAs($owner->user)->putJson(route('api.qr.update'), $this->design(['name' => 'Card Name']))->assertOk();

        $this->get(route('public.qr', $owner->slug))->assertOk()->assertSee('Card Name');

        // Model deletes fire the cache flush; a bulk query delete would not.
        RestaurantFeature::where('restaurant_id', $owner->id)->get()->each->delete();

        $this->get(route('public.qr', $owner->slug))->assertNotFound();
    }

    public function test_the_card_is_not_found_for_an_unknown_or_reserved_slug(): void
    {
        $this->get('/no-such-place/qr')->assertNotFound();
        $this->get('/admin/qr')->assertNotFound();
    }

    public function test_stats_are_scoped_to_the_owner(): void
    {
        $owner = $this->unlocked();
        $other = $this->published();
        $other->statistics()->create(['session_id' => 'x', 'via_qr' => true, 'viewed_at' => now()]);

        $this->actingAs($owner->user)->getJson(route('api.qr.show'))->assertJsonPath('data.stats.total', 0);
    }
}
