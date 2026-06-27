<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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

    public function test_final_step_completes_onboarding(): void
    {
        // Step 5 is the final step — a valid tag completes onboarding rather than
        // returning a further step.
        Mail::fake();
        $tag = Tag::create(['name' => ['en' => 'Cozy'], 'slug' => 'cozy', 'category' => 'vibe']);
        $user = $this->ownerWithRestaurant(4);

        $this->actingAs($user)
            ->postJson(route('onboarding.advance'), [
                '_step' => 5,
                'tag_ids' => [$tag->id],
            ])
            ->assertOk()
            ->assertJson(['completed' => true]);

        $this->assertNotNull($user->fresh()->onboarding_completed_at);
    }

    public function test_overshooting_step_is_clamped_to_final_step(): void
    {
        // A wildly out-of-range step must collapse onto the final step (tag
        // selection) instead of returning 8, 9, … to infinity.
        $this->actingAs($this->ownerWithRestaurant(5))
            ->postJson(route('onboarding.advance'), [
                '_step' => 12,
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('tag_ids');
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
