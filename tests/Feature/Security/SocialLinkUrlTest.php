<?php

namespace Tests\Feature\Security;

use App\Models\Restaurant;
use App\Models\RestaurantSocialLink;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SocialLinkUrlTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithRestaurant(): User
    {
        $owner = User::factory()->create(['onboarding_completed_at' => now()]);
        Restaurant::factory()->create(['user_id' => $owner->id]);

        return $owner;
    }

    public function test_javascript_scheme_url_is_rejected(): void
    {
        $owner = $this->ownerWithRestaurant();

        $response = $this->actingAs($owner)->post(route('menu-owner.social-links.store'), [
            'platform' => 'instagram',
            'url' => 'javascript:alert(1)',
        ]);

        $response->assertSessionHasErrors('url');
        $this->assertDatabaseMissing('restaurant_social_links', [
            'url' => 'javascript:alert(1)',
        ]);
    }

    public function test_data_scheme_url_is_rejected(): void
    {
        $owner = $this->ownerWithRestaurant();

        $response = $this->actingAs($owner)->post(route('menu-owner.social-links.store'), [
            'platform' => 'instagram',
            'url' => 'data:text/html,<script>alert(1)</script>',
        ]);

        $response->assertSessionHasErrors('url');
        $this->assertSame(0, RestaurantSocialLink::query()->count());
    }

    public function test_empty_url_surfaces_a_required_message(): void
    {
        $owner = $this->ownerWithRestaurant();

        $response = $this->actingAs($owner)->post(route('menu-owner.social-links.store'), [
            'platform' => 'instagram',
            'url' => '',
        ]);

        $response->assertSessionHasErrors(['url' => 'Please enter the link URL.']);
        $this->assertSame(0, RestaurantSocialLink::query()->count());
    }

    public function test_normal_https_url_is_accepted(): void
    {
        $owner = $this->ownerWithRestaurant();

        $response = $this->actingAs($owner)->post(route('menu-owner.social-links.store'), [
            'platform' => 'instagram',
            'url' => 'https://instagram.com/menux',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('restaurant_social_links', [
            'restaurant_id' => $owner->restaurant->id,
            'platform' => 'instagram',
            'url' => 'https://instagram.com/menux',
        ]);
    }
}
