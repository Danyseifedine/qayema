<?php

namespace Tests\Feature\Api;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array{0: User, 1: Restaurant}
     */
    private function owner(): array
    {
        $user = User::factory()->create();
        $restaurant = Restaurant::factory()->create(['user_id' => $user->id]);

        return [$user, $restaurant];
    }

    /**
     * A valid update body — name, phone and currency are the required fields.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'My Restaurant',
            'phone' => '+961 70 123 456',
            'currency' => 'USD',
        ], $overrides);
    }

    private function uploadTempImage(User $user, string $context): string
    {
        return $this->actingAs($user)->post(
            route('api.uploads.temp'),
            ['file' => UploadedFile::fake()->image('img.jpg', 600, 600), 'context' => $context],
            ['Accept' => 'application/json'],
        )->assertOk()->json('key');
    }

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson(route('api.settings.show'))->assertUnauthorized();
    }

    public function test_show_returns_the_profile_and_currencies(): void
    {
        [$user, $restaurant] = $this->owner();

        $this->actingAs($user)
            ->getJson(route('api.settings.show'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'name' => ['en', 'ar'],
                    'description' => ['en', 'ar'],
                    'default_locale', 'slug', 'google_maps_url', 'phone',
                    'country_code', 'currency', 'opening_hours', 'timezone',
                    'logo_url', 'cover_url',
                ],
                'meta' => ['currencies' => [['code', 'name', 'symbol']]],
            ])
            ->assertJsonPath('data.slug', $restaurant->slug);
    }

    public function test_update_saves_the_description_and_map_url(): void
    {
        [$user, $restaurant] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload([
                'description' => 'Best mezze in town',
                'google_maps_url' => 'https://maps.google.com/?q=33.8886,35.4955',
            ]))
            ->assertOk()
            ->assertJsonPath('data.google_maps_url', 'https://maps.google.com/?q=33.8886,35.4955');

        $restaurant->refresh();

        $this->assertSame(
            'Best mezze in town',
            $restaurant->getTranslation('description', $restaurant->default_locale),
        );
    }

    public function test_opening_hours_round_trip_with_a_timezone(): void
    {
        [$user, $restaurant] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload([
                'timezone' => 'Asia/Beirut',
                'opening_hours' => [
                    'mon' => ['open' => '07:30', 'close' => '22:00'],
                    'tue' => null,
                ],
            ]))
            ->assertOk()
            ->assertJsonPath('data.timezone', 'Asia/Beirut')
            ->assertJsonPath('data.opening_hours.mon', ['open' => '07:30', 'close' => '22:00'])
            ->assertJsonPath('data.opening_hours.tue', null)
            // The whole week always comes back, so the form never guesses.
            ->assertJsonPath('data.opening_hours.sun', null);

        $this->assertSame('Asia/Beirut', $restaurant->fresh()->timezone);
    }

    public function test_a_half_written_range_is_rejected(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload([
                'opening_hours' => ['mon' => ['open' => '07:30']],
            ]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('opening_hours.mon.close');
    }

    public function test_an_unreal_timezone_is_rejected(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['timezone' => 'Middle/Earth']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('timezone');
    }

    public function test_update_rejects_a_malformed_map_url(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['google_maps_url' => 'not-a-url']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('google_maps_url');
    }

    public function test_update_changes_phone_country_code_and_currency(): void
    {
        [$user, $restaurant] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload([
                'phone' => '+971 50 111 2222',
                'country_code' => 'AE',
                'currency' => 'AED',
            ]))
            ->assertOk();

        $restaurant->refresh();
        $this->assertSame('+971 50 111 2222', $restaurant->phone);
        $this->assertSame('AE', $restaurant->country_code);
        $this->assertSame('AED', $restaurant->currency);
    }

    public function test_update_requires_a_phone(): void
    {
        [$user] = $this->owner();
        $payload = $this->basePayload();
        unset($payload['phone']);

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_update_rejects_an_unknown_currency(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['currency' => 'NOPE']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('currency');
    }

    public function test_update_changes_the_display_name(): void
    {
        // The restaurant's default locale is 'en' (factory), so the name is written
        // there.
        [$user, $restaurant] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['name' => 'Renamed Bistro']))
            ->assertOk()
            ->assertJsonPath('data.name.en', 'Renamed Bistro');

        $this->assertSame('Renamed Bistro', $restaurant->fresh()->getTranslation('name', 'en'));
    }

    public function test_update_requires_a_name(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['name' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_rejects_a_one_character_name(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['name' => 'x']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_writes_the_name_to_the_restaurants_own_locale(): void
    {
        // An Arabic-default restaurant gets the name written to 'ar', not 'en'.
        [$user, $restaurant] = $this->owner();
        $restaurant->update(['default_locale' => 'ar']);

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['name' => 'مطعم']))
            ->assertOk();

        $this->assertSame('مطعم', $restaurant->fresh()->getTranslation('name', 'ar'));
    }

    public function test_update_rejects_a_name_with_control_characters(): void
    {
        // An interior control character must be a clean 422, never a 500 from a
        // corrupted JSON name column.
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['name' => "Bad\nName"]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('name');
    }

    public function test_update_rejects_an_over_long_country_code(): void
    {
        // The column is char(2); a longer value must be rejected up front rather
        // than 500-ing on save under strict mode.
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['country_code' => 'ABCDE']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('country_code');
    }

    public function test_update_rejects_a_phone_with_interior_whitespace(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['phone' => "70\n123456"]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }

    public function test_update_cannot_change_the_slug(): void
    {
        [$user, $restaurant] = $this->owner();
        $slug = $restaurant->slug;

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['slug' => 'hacked']))
            ->assertOk();

        $this->assertSame($slug, $restaurant->fresh()->slug);
    }

    public function test_update_attaches_a_temp_uploaded_logo(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $key = $this->uploadTempImage($user, 'logo');

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['logo_key' => $key]))
            ->assertOk();

        $this->assertSame(1, $restaurant->fresh()->getMedia('logo')->count());
    }

    public function test_the_logo_cannot_be_cleared(): void
    {
        Storage::fake(config('media-library.disk_name'));
        [$user, $restaurant] = $this->owner();
        $restaurant->addMedia(UploadedFile::fake()->image('logo.png'))->toMediaCollection('logo');

        // delete_logo is not an accepted field — the mandatory logo must survive.
        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['delete_logo' => true]))
            ->assertOk();

        $this->assertSame(1, $restaurant->fresh()->getMedia('logo')->count());
    }

    public function test_update_rejects_a_malformed_logo_key(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['logo_key' => 'not-a-uuid']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('logo_key');
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson(route('api.settings.show'))->assertForbidden();
        $this->actingAs($user)->putJson(route('api.settings.update'), $this->basePayload())->assertForbidden();
    }
}
