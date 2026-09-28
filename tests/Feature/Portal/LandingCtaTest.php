<?php

namespace Tests\Feature\Portal;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingCtaTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_the_sign_up_cta(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee(route('register'), false);
    }

    public function test_authenticated_owner_mid_onboarding_is_pointed_to_onboarding(): void
    {
        // A logged-in owner must never see the guest-only sign-up link (it bounces
        // them back here); mid-onboarding they are sent to continue setup.
        $user = User::factory()->create(['onboarding_completed_at' => null]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee(route('onboarding'), false);
        $response->assertDontSee(route('register'), false);
    }

    public function test_the_pricing_section_lists_the_four_packages(): void
    {
        $response = $this->get('/')->assertOk();

        foreach (['Free', 'Pro', 'Premium', 'Custom'] as $tier) {
            $response->assertSee($tier, false);
        }
    }

    public function test_the_custom_package_points_at_the_contact_page_not_registration(): void
    {
        // Custom has no published price, so there is nothing to sign up
        // against: the owner has to talk to us first.
        $html = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '#<a [^>]*href="'.preg_quote(route('contact'), '#').'"[^>]*>\s*'.preg_quote(__('portal.pricing.cta_contact'), '#').'\s*</a>#',
            $html,
            'The Custom call to action does not link to the contact page.',
        );
    }

    public function test_onboarded_owner_is_pointed_to_the_dashboard(): void
    {
        config(['app.dashboard_url' => 'https://dash.qayema.test']);
        $user = User::factory()->create(['onboarding_completed_at' => now()]);

        $response = $this->actingAs($user)->get('/');

        $response->assertOk();
        $response->assertSee('https://dash.qayema.test', false);
        $response->assertDontSee(route('register'), false);
    }
}
