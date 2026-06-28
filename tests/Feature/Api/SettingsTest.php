<?php

namespace Tests\Feature\Api;

use App\Models\Restaurant;
use App\Models\Tag;
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

    private function tag(string $category, string $slug): Tag
    {
        return Tag::create(['name' => ['en' => ucfirst($slug)], 'slug' => $slug, 'category' => $category]);
    }

    /**
     * A valid update body. Name, phone, currency and at least one tag per category
     * are required.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function basePayload(array $overrides = []): array
    {
        // One tag per category — the update requires at least one of each.
        $tagIds = collect(Tag::OWNER_CATEGORIES)
            ->map(fn (string $category): int => Tag::firstOrCreate(
                ['slug' => "base-{$category}"],
                ['name' => ['en' => ucfirst($category)], 'category' => $category],
            )->id)
            ->all();

        return array_merge([
            'name' => 'My Restaurant',
            'phone' => '+961 70 123 456',
            'currency' => 'USD',
            'tag_ids' => $tagIds,
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

    public function test_show_returns_the_settings_tags_and_currencies(): void
    {
        [$user, $restaurant] = $this->owner();
        $lebanese = $this->tag('cuisine', 'lebanese');
        $vegan = $this->tag('dietary', 'vegan');
        $rooftop = $this->tag('vibe', 'rooftop');
        $dark = $this->tag('style', 'dark');
        $restaurant->tags()->attach([$lebanese->id, $rooftop->id]);

        $response = $this->actingAs($user)->getJson(route('api.settings.show'))->assertOk();

        $response->assertJsonStructure([
            'data' => ['name' => ['en', 'ar'], 'default_locale', 'slug', 'phone', 'country_code', 'currency', 'logo_url', 'cover_url', 'tag_ids'],
            'meta' => [
                'tags' => [['id', 'slug', 'category', 'name' => ['en', 'ar']]],
                'currencies' => [['code', 'name', 'symbol']],
            ],
        ]);
        $response->assertJsonPath('data.slug', $restaurant->slug);

        // All four categories are selectable.
        $offered = array_column($response->json('meta.tags'), 'id');
        foreach ([$lebanese, $vegan, $rooftop, $dark] as $tag) {
            $this->assertContains($tag->id, $offered);
        }

        // tag_ids carries every owner-category tag attached to the restaurant.
        $this->assertEqualsCanonicalizing([$lebanese->id, $rooftop->id], $response->json('data.tag_ids'));
    }

    public function test_resubmitting_what_show_returns_does_not_fail(): void
    {
        // The SPA seeds its picker from data.tag_ids and resubmits it on save — that
        // round-trip must never 422. An onboarded restaurant has one tag per category.
        [$user, $restaurant] = $this->owner();
        $ids = collect(['cuisine', 'dietary', 'vibe', 'style'])
            ->map(fn (string $category): int => $this->tag($category, "rt-{$category}")->id)
            ->all();
        $restaurant->tags()->attach($ids);

        $tagIds = $this->actingAs($user)->getJson(route('api.settings.show'))->assertOk()->json('data.tag_ids');

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['tag_ids' => $tagIds]))
            ->assertOk();

        $this->assertEqualsCanonicalizing($ids, $restaurant->tags()->pluck('tags.id')->all());
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

    public function test_update_requires_at_least_one_tag(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['tag_ids' => []]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tag_ids');
    }

    public function test_update_requires_a_tag_in_each_category(): void
    {
        [$user] = $this->owner();
        $cuisine = $this->tag('cuisine', 'lebanese');

        // Only a cuisine tag — missing dietary, vibe and style.
        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['tag_ids' => [$cuisine->id]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tag_ids');
    }

    public function test_update_syncs_owner_tags_across_all_categories(): void
    {
        [$user, $restaurant] = $this->owner();
        $lebanese = $this->tag('cuisine', 'lebanese');
        $vegan = $this->tag('dietary', 'vegan');
        $rooftop = $this->tag('vibe', 'rooftop');
        $dark = $this->tag('style', 'dark');

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['tag_ids' => [$lebanese->id, $vegan->id, $rooftop->id, $dark->id]]))
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            [$lebanese->id, $vegan->id, $rooftop->id, $dark->id],
            $restaurant->tags()->pluck('tags.id')->all(),
        );
    }

    public function test_update_rejects_an_unknown_tag(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->putJson(route('api.settings.update'), $this->basePayload(['tag_ids' => [999999]]))
            ->assertStatus(422)
            ->assertJsonValidationErrors('tag_ids.0');
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
