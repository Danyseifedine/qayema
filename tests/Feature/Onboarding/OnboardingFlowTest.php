<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_step_three_does_not_require_a_logo(): void
    {
        // User has completed steps 1 & 2 (onboarding_step = 2) and has a restaurant.
        $user = User::factory()->create(['onboarding_step' => 2, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        // Advancing step 3 with NO logo must succeed (logo is optional now), not 422.
        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 3]);

        $response->assertOk();
        $response->assertJsonMissingValidationErrors('logo_key');
        $this->assertSame(3, $user->fresh()->onboarding_step);
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
