<?php

namespace Tests\Feature\Onboarding;

use App\Mail\WelcomeRestaurantOwner;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_three_requires_a_logo(): void
    {
        // User has completed steps 1 & 2 (onboarding_step = 2) and has a restaurant
        // with no logo yet.
        $user = User::factory()->create(['onboarding_step' => 2, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        // Advancing step 3 with NO logo must now fail — the logo is required.
        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 3]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('logo_key');
        $this->assertSame(2, $user->fresh()->onboarding_step);
    }

    public function test_step_three_accepts_a_logo_and_completes_onboarding(): void
    {
        // Step 3 (branding) is the final step: a logo completes onboarding and
        // sends the welcome email. No template is assigned — the owner picks one
        // from the dashboard, which stays locked until they do.
        Mail::fake();
        $user = User::factory()->create(['onboarding_step' => 2, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        // A well-formed temp-upload key satisfies the requirement (the key format is
        // a 36-char UUID; saveBranding no-ops when the temp file is absent).
        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), [
            '_step' => 3,
            'logo_key' => '11111111-1111-1111-1111-111111111111',
        ]);

        $response->assertOk()->assertJson(['completed' => true]);
        $response->assertJsonMissingValidationErrors('logo_key');

        $user->refresh();
        $this->assertSame(User::ONBOARDING_STEPS, $user->onboarding_step);
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertNull($user->restaurant->template_id);
        Mail::assertQueued(WelcomeRestaurantOwner::class);
    }

    public function test_slug_taken_by_another_restaurant_is_reported_as_taken(): void
    {
        Restaurant::factory()->create(['slug' => 'taken-slug']);
        $user = User::factory()->create(['onboarding_completed_at' => null]);

        $response = $this->actingAs($user)->getJson(route('onboarding.check-slug', ['slug' => 'taken-slug']));

        $response->assertOk();
        $response->assertJson(['available' => false]);
    }

    public function test_users_own_slug_is_available_to_themselves(): void
    {
        $user = User::factory()->create(['onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id, 'slug' => 'my-own-slug']);

        $response = $this->actingAs($user)->getJson(route('onboarding.check-slug', ['slug' => 'my-own-slug']));

        $response->assertOk();
        $response->assertJson(['available' => true]);
    }
}
