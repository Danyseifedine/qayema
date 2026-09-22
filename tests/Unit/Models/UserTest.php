<?php

namespace Tests\Unit\Models;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_roles_are_mutually_exclusive(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isMenuOwner());
        $this->assertTrue($owner->isMenuOwner());
        $this->assertFalse($owner->isAdmin());
    }

    public function test_only_admins_reach_the_panel_and_only_owners_can_be_impersonated(): void
    {
        $admin = User::factory()->create(['role' => UserRole::Admin]);
        $owner = User::factory()->create(['role' => UserRole::MenuOwner]);

        $this->assertTrue($admin->canImpersonate());
        $this->assertFalse($owner->canImpersonate());
        $this->assertTrue($owner->canBeImpersonated());
        $this->assertFalse($admin->canBeImpersonated());
    }

    public function test_the_onboarding_step_shown_is_one_past_the_saved_one_capped_at_the_last(): void
    {
        $this->assertSame(1, User::factory()->make(['onboarding_step' => 0])->currentOnboardingStep());
        $this->assertSame(3, User::factory()->make(['onboarding_step' => 2])->currentOnboardingStep());
        $this->assertSame(User::ONBOARDING_STEPS, User::factory()->make(['onboarding_step' => 99])->currentOnboardingStep());
    }

    public function test_after_login_goes_to_the_dashboard_only_once_onboarded(): void
    {
        config(['app.dashboard_url' => 'https://dashboard.example.test']);

        $fresh = User::factory()->make(['onboarding_completed_at' => null]);
        $done = User::factory()->make(['onboarding_completed_at' => now()]);

        $this->assertSame(route('onboarding'), $fresh->afterLoginUrl());
        $this->assertSame('https://dashboard.example.test', $done->afterLoginUrl());
    }

    public function test_sensitive_attributes_are_hidden_from_serialisation(): void
    {
        $array = User::factory()->create()->toArray();

        $this->assertArrayNotHasKey('password', $array);
        $this->assertArrayNotHasKey('remember_token', $array);
    }
}
