<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Models\FeatureDefault;
use App\Models\RestaurantSocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class SocialLinkEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_dangerous_url_schemes_are_rejected(): void
    {
        $owner = $this->owner();

        foreach (['javascript:alert(1)', 'ftp://x.test', 'data:text/html,hi', 'instagram.com/me', '//evil.test'] as $bad) {
            $this->actingAs($owner->user)
                ->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => $bad])
                ->assertStatus(422, $bad)->assertJsonValidationErrors('url');
        }
    }

    public function test_the_url_length_limit(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => 'https://x.test/'.str_repeat('a', 500)])
            ->assertStatus(422)->assertJsonValidationErrors('url');
    }

    public function test_every_supported_platform_is_accepted_and_others_are_not(): void
    {
        FeatureDefault::set(Feature::SocialLinkLimit, 10);
        $owner = $this->owner();

        foreach (RestaurantSocialLink::PLATFORMS as $platform) {
            $this->actingAs($owner->user)
                ->postJson(route('api.social-links.store'), ['platform' => $platform, 'url' => "https://{$platform}.test/me"])
                ->assertCreated();
        }

        $this->actingAs($owner->user)
            ->postJson(route('api.social-links.store'), ['platform' => 'Instagram', 'url' => 'https://x.test'])
            ->assertStatus(422, 'Platform keys are case-sensitive and lowercase.');
    }

    public function test_the_platform_cannot_be_switched_onto_one_already_used(): void
    {
        $owner = $this->owner();
        $insta = RestaurantSocialLink::factory()->create(['restaurant_id' => $owner->id, 'platform' => 'instagram']);
        RestaurantSocialLink::factory()->create(['restaurant_id' => $owner->id, 'platform' => 'x']);

        $this->actingAs($owner->user)
            ->putJson(route('api.social-links.update', $insta), ['platform' => 'x', 'url' => 'https://x.com/me'])
            ->assertStatus(422)->assertJsonValidationErrors('platform');
    }

    public function test_the_same_platform_is_fine_across_different_restaurants(): void
    {
        RestaurantSocialLink::factory()->create(['platform' => 'instagram']);
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => 'https://instagram.com/me'])
            ->assertCreated();
    }

    public function test_deleting_frees_the_slot_and_the_platform(): void
    {
        FeatureDefault::set(Feature::SocialLinkLimit, 1);
        $owner = $this->owner();
        $link = RestaurantSocialLink::factory()->create(['restaurant_id' => $owner->id, 'platform' => 'instagram']);

        $this->actingAs($owner->user)->postJson(route('api.social-links.store'), ['platform' => 'x', 'url' => 'https://x.com/me'])->assertStatus(422);
        $this->actingAs($owner->user)->deleteJson(route('api.social-links.destroy', $link))->assertNoContent();
        $this->actingAs($owner->user)->postJson(route('api.social-links.store'), ['platform' => 'instagram', 'url' => 'https://instagram.com/new'])->assertCreated();
    }

    public function test_the_index_reports_used_and_limit(): void
    {
        $owner = $this->owner();
        RestaurantSocialLink::factory()->create(['restaurant_id' => $owner->id, 'platform' => 'tiktok']);

        $this->actingAs($owner->user)->getJson(route('api.social-links.index'))
            ->assertOk()->assertJsonPath('meta.used', 1)->assertJsonPath('meta.limit', 2);
    }
}
