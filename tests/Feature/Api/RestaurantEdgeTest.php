<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class RestaurantEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::MultipleLanguages);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge(['name' => ['en' => 'My Place'], 'phone' => '+961 70 123 456', 'currency' => 'USD'], $overrides);
    }

    public function test_description_length_limit(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['description' => ['en' => str_repeat('x', 2000)]]))->assertOk();
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['description' => ['en' => str_repeat('x', 2001)]]))->assertStatus(422)->assertJsonValidationErrors('description.en');
    }

    public function test_a_written_address_is_not_accepted_any_more(): void
    {
        // The restaurant is located by its map link alone, so a stray address
        // in the payload is ignored rather than stored.
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->putJson(route('api.restaurant.update'), $this->payload(['address' => 'Hamra Street']))
            ->assertOk();

        $this->assertArrayNotHasKey('address', $owner->fresh()->getAttributes());
    }

    public function test_the_map_link_must_be_a_web_url(): void
    {
        $owner = $this->owner();

        foreach (['javascript:alert(1)', 'ftp://maps', 'maps.google.com/x', 'data:text/html,hi'] as $bad) {
            $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['google_maps_url' => $bad]))
                ->assertStatus(422, $bad)->assertJsonValidationErrors('google_maps_url');
        }

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['google_maps_url' => 'https://maps.app.goo.gl/abc']))->assertOk();
    }

    public function test_clearing_optional_fields_with_null_works(): void
    {
        $owner = $this->owner(['google_maps_url' => 'https://maps.google.com/x', 'description' => ['en' => 'old']]);

        $this->actingAs($owner->user)
            ->putJson(route('api.restaurant.update'), $this->payload(['google_maps_url' => null, 'description' => null]))
            ->assertOk()
            ->assertJsonPath('data.google_maps_url', null)
            ->assertJsonPath('data.description', ['en' => null, 'ar' => null]);
    }

    public function test_country_code_is_two_ascii_letters_in_either_case(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['country_code' => 'lb']))->assertOk();
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['country_code' => 'L1']))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['country_code' => 'LBN']))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['country_code' => null]))->assertOk();
    }

    public function test_phone_boundaries(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['phone' => '123456']))->assertOk();
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['phone' => '12345']))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['phone' => str_repeat('1', 31)]))->assertStatus(422);
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['phone' => '+961 (70) 123-456']))->assertOk();
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['phone' => '0700 CALL ME']))->assertStatus(422);
    }

    public function test_replacing_the_logo_never_leaves_two(): void
    {
        $owner = $this->owner();
        $upload = fn () => $this->actingAs($owner->user)->post(route('api.uploads.temp'),
            ['file' => \Illuminate\Http\UploadedFile::fake()->image('l.png', 100, 100), 'context' => 'logo'],
            ['Accept' => 'application/json'])->json('key');

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['logo_key' => $upload()]))->assertOk();
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['logo_key' => $upload()]))->assertOk();

        $this->assertCount(1, $owner->fresh()->getMedia('logo'));
    }

    public function test_the_cover_can_be_removed_then_re_added(): void
    {
        $owner = $this->owner();
        $owner->addMedia(\Illuminate\Http\UploadedFile::fake()->image('c.jpg'))->toMediaCollection('cover_image');

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['delete_cover_image' => true]))->assertOk()->assertJsonPath('data.cover_url', null);

        $key = $this->actingAs($owner->user)->post(route('api.uploads.temp'),
            ['file' => \Illuminate\Http\UploadedFile::fake()->image('c.png', 400, 200), 'context' => 'cover_image'],
            ['Accept' => 'application/json'])->json('key');
        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['cover_image_key' => $key]))->assertOk();

        $this->assertCount(1, $owner->fresh()->getMedia('cover_image'));
    }

    public function test_text_in_a_language_the_menu_no_longer_uses_is_kept(): void
    {
        // Arabic today, French before: the French name must survive an edit.
        $owner = $this->owner(['second_locale' => 'ar', 'name' => ['en' => 'Olive', 'fr' => 'Olivier']]);

        $this->actingAs($owner->user)
            ->putJson(route('api.restaurant.update'), $this->payload(['name' => ['en' => 'Olive', 'ar' => 'زيتون']]))
            ->assertOk()
            ->assertJsonPath('data.name', ['en' => 'Olive', 'ar' => 'زيتون']);

        $this->assertSame('Olivier', $owner->fresh()->getTranslation('name', 'fr', false));
    }

    public function test_a_settings_save_never_changes_the_languages(): void
    {
        $owner = $this->owner(['second_locale' => 'fr', 'default_locale' => 'fr']);

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload())->assertOk();

        $this->assertSame('fr', $owner->fresh()->second_locale);
        $this->assertSame('fr', $owner->fresh()->default_locale);
    }

    public function test_is_active_cannot_be_flipped_by_the_owner(): void
    {
        $owner = $this->owner(['is_active' => true]);

        $this->actingAs($owner->user)->putJson(route('api.restaurant.update'), $this->payload(['is_active' => false]))->assertOk();

        $this->assertTrue($owner->fresh()->is_active);
    }
}
