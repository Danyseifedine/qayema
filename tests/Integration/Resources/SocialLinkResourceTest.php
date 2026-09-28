<?php

namespace Tests\Integration\Resources;

use App\Http\Resources\SocialLinkResource;
use App\Models\RestaurantSocialLink;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

class SocialLinkResourceTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_the_shape_is_id_platform_and_url_only(): void
    {
        $link = RestaurantSocialLink::factory()->create([
            'platform' => 'instagram',
            'url' => 'https://instagram.com/qayema',
        ]);

        $this->assertSame([
            'id' => $link->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/qayema',
        ], (new SocialLinkResource($link))->resolve(Request::create('/')));
    }

    public function test_the_endpoint_sends_the_same_shape(): void
    {
        $restaurant = $this->owner();
        $link = RestaurantSocialLink::factory()->for($restaurant)->create(['platform' => 'x', 'url' => 'https://x.com/qayema']);

        $this->actingAs($restaurant->user)
            ->getJson(route('api.social-links.index'))
            ->assertOk()
            ->assertJsonPath('data', [['id' => $link->id, 'platform' => 'x', 'url' => 'https://x.com/qayema']]);
    }
}
