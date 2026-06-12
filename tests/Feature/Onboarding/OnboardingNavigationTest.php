<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingNavigationTest extends TestCase
{
    use RefreshDatabase;

    private function ownerWithRestaurant(int $step): User
    {
        $user = User::factory()->create(['onboarding_step' => $step]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        return $user;
    }

    public function test_returned_step_never_exceeds_total_steps(): void
    {
        $this->actingAs($this->ownerWithRestaurant(4))
            ->postJson(route('onboarding.advance'), [
                '_step' => 5,
                'tag_ids' => [],
            ])
            ->assertOk()
            ->assertJson(['step' => 6]);
    }

    public function test_overshooting_step_is_clamped_to_final_step(): void
    {
        // A wildly out-of-range step must collapse onto the final step (template
        // selection) instead of returning 8, 9, … to infinity.
        $this->actingAs($this->ownerWithRestaurant(5))
            ->postJson(route('onboarding.advance'), [
                '_step' => 12,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('template_id');
    }

    public function test_later_step_without_restaurant_is_sent_back_to_step_one(): void
    {
        $user = User::factory()->create(['onboarding_step' => 0]);

        $this->actingAs($user)
            ->postJson(route('onboarding.advance'), [
                '_step' => 4,
                'tag_ids' => [],
            ])
            ->assertOk()
            ->assertJson(['step' => 1]);
    }
}
