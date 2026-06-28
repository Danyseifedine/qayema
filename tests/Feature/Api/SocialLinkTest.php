<?php

namespace Tests\Feature\Api;

use App\Models\PackageDefault;
use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinkTest extends TestCase
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

    public function test_unauthenticated_requests_are_rejected(): void
    {
        $this->getJson(route('api.social-links.index'))->assertUnauthorized();
    }

    public function test_index_returns_only_the_users_links_with_meta(): void
    {
        PackageDefault::set('social_link_limit', 4);
        [$user, $restaurant] = $this->owner();
        RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id, 'platform' => 'instagram']);
        RestaurantSocialLink::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $response = $this->actingAs($user)->getJson(route('api.social-links.index'))->assertOk();

        $response->assertJsonCount(1, 'data');
        $response->assertJsonStructure([
            'data' => [['id', 'platform', 'url']],
            'meta' => ['used', 'limit'],
        ]);
        $response->assertJsonPath('meta.used', 1)->assertJsonPath('meta.limit', 4);
    }

    public function test_store_creates_a_link(): void
    {
        [$user, $restaurant] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => 'https://instagram.com/qayema'])
            ->assertCreated()
            ->assertJsonPath('data.platform', 'instagram')
            ->assertJsonPath('data.url', 'https://instagram.com/qayema');

        $this->assertDatabaseHas('restaurant_social_links', [
            'restaurant_id' => $restaurant->id,
            'platform' => 'instagram',
        ]);
    }

    public function test_store_requires_a_platform_and_url(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.social-links.store'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['platform', 'url']);
    }

    public function test_store_rejects_an_unknown_platform(): void
    {
        [$user] = $this->owner();

        $this->actingAs($user)
            ->postJson(route('api.social-links.store'), ['platform' => 'myspace', 'url' => 'https://myspace.com/x'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('platform');
    }

    public function test_store_rejects_a_non_http_url(): void
    {
        [$user] = $this->owner();

        // javascript: must never reach the public menu's href.
        $this->actingAs($user)
            ->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => 'javascript:alert(1)'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('url');
    }

    public function test_store_rejects_a_duplicate_platform(): void
    {
        [$user, $restaurant] = $this->owner();
        RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id, 'platform' => 'instagram']);

        $this->actingAs($user)
            ->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => 'https://instagram.com/again'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('platform');
    }

    public function test_store_is_rejected_when_the_limit_is_reached(): void
    {
        PackageDefault::set('social_link_limit', 1);
        [$user, $restaurant] = $this->owner();
        RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id, 'platform' => 'instagram']);

        $this->actingAs($user)
            ->postJson(route('api.social-links.store'), ['platform' => 'facebook', 'url' => 'https://facebook.com/qayema'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('platform');

        $this->assertDatabaseCount('restaurant_social_links', 1);
    }

    public function test_store_ignores_a_spoofed_restaurant_id(): void
    {
        [$user, $restaurant] = $this->owner();
        $victim = Restaurant::factory()->create();

        $this->actingAs($user)
            ->postJson(route('api.social-links.store'), [
                'platform' => 'x',
                'url' => 'https://x.com/qayema',
                'restaurant_id' => $victim->id,
            ])
            ->assertCreated();

        $this->assertDatabaseHas('restaurant_social_links', ['platform' => 'x', 'restaurant_id' => $restaurant->id]);
        $this->assertDatabaseMissing('restaurant_social_links', ['restaurant_id' => $victim->id]);
    }

    public function test_a_user_can_update_their_own_link(): void
    {
        [$user, $restaurant] = $this->owner();
        $link = RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id, 'platform' => 'instagram']);

        $this->actingAs($user)
            ->putJson(route('api.social-links.update', $link), ['platform' => 'instagram', 'url' => 'https://instagram.com/new'])
            ->assertOk()
            ->assertJsonPath('data.url', 'https://instagram.com/new');

        $this->assertSame('https://instagram.com/new', $link->refresh()->url);
    }

    public function test_update_allows_keeping_the_same_platform(): void
    {
        [$user, $restaurant] = $this->owner();
        $link = RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id, 'platform' => 'instagram']);

        // The uniqueness rule must ignore the record being updated.
        $this->actingAs($user)
            ->putJson(route('api.social-links.update', $link), ['platform' => 'instagram', 'url' => 'https://instagram.com/same'])
            ->assertOk();
    }

    public function test_a_user_cannot_update_another_restaurants_link(): void
    {
        [$user] = $this->owner();
        $foreign = RestaurantSocialLink::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->putJson(route('api.social-links.update', $foreign), ['platform' => 'facebook', 'url' => 'https://facebook.com/hijack'])
            ->assertForbidden();
    }

    public function test_a_user_can_delete_their_own_link(): void
    {
        [$user, $restaurant] = $this->owner();
        $link = RestaurantSocialLink::factory()->create(['restaurant_id' => $restaurant->id]);

        $this->actingAs($user)
            ->deleteJson(route('api.social-links.destroy', $link))
            ->assertNoContent();

        $this->assertDatabaseMissing('restaurant_social_links', ['id' => $link->id]);
    }

    public function test_a_user_cannot_delete_another_restaurants_link(): void
    {
        [$user] = $this->owner();
        $foreign = RestaurantSocialLink::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)
            ->deleteJson(route('api.social-links.destroy', $foreign))
            ->assertForbidden();

        $this->assertDatabaseHas('restaurant_social_links', ['id' => $foreign->id]);
    }

    public function test_a_user_without_a_restaurant_is_forbidden(): void
    {
        $user = User::factory()->create();
        $link = RestaurantSocialLink::factory()->create(['restaurant_id' => Restaurant::factory()->create()->id]);

        $this->actingAs($user)->getJson(route('api.social-links.index'))->assertForbidden();
        $this->actingAs($user)
            ->putJson(route('api.social-links.update', $link), ['platform' => 'x', 'url' => 'https://x.com/x'])
            ->assertForbidden();
        $this->actingAs($user)->deleteJson(route('api.social-links.destroy', $link))->assertForbidden();
    }
}
