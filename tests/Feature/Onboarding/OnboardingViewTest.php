<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingViewTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_onboarding_page_renders(): void
    {
        $user = User::factory()->create(['onboarding_step' => 0, 'onboarding_completed_at' => null]);

        $this->actingAs($user)->get(route('onboarding'))->assertOk();
    }

    public function test_the_onboarding_page_renders_mid_flow(): void
    {
        $user = User::factory()->create(['onboarding_step' => 2, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('onboarding'))->assertOk();
    }
}
