<?php

namespace Tests\Feature\Onboarding;

use App\Mail\WelcomeRestaurantOwner;
use App\Models\Restaurant;
use App\Models\Tag;
use App\Models\Template;
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

    public function test_step_three_accepts_a_logo(): void
    {
        $user = User::factory()->create(['onboarding_step' => 2, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        // A well-formed temp-upload key satisfies the requirement (the key format is
        // a 36-char UUID; saveBranding no-ops when the temp file is absent).
        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), [
            '_step' => 3,
            'logo_key' => '11111111-1111-1111-1111-111111111111',
        ]);

        $response->assertOk();
        $response->assertJsonMissingValidationErrors('logo_key');
        $this->assertSame(3, $user->fresh()->onboarding_step);
    }

    public function test_step_four_requires_a_tag(): void
    {
        $user = User::factory()->create(['onboarding_step' => 3, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 4]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tag_ids');
        $this->assertSame(3, $user->fresh()->onboarding_step);
    }

    public function test_step_four_accepts_a_tag(): void
    {
        $user = User::factory()->create(['onboarding_step' => 3, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);
        $cuisine = Tag::create(['name' => ['en' => 'Italian'], 'slug' => 'italian', 'category' => 'cuisine']);
        $dietary = Tag::create(['name' => ['en' => 'Vegan'], 'slug' => 'vegan', 'category' => 'dietary']);

        // Step 4 requires one cuisine AND one dietary tag.
        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 4, 'tag_ids' => [$cuisine->id, $dietary->id]]);

        $response->assertOk();
        $response->assertJsonMissingValidationErrors('tag_ids');
        $this->assertSame(4, $user->fresh()->onboarding_step);
    }

    public function test_step_five_requires_a_tag(): void
    {
        $user = User::factory()->create(['onboarding_step' => 4, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 5]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors('tag_ids');
        $this->assertSame(4, $user->fresh()->onboarding_step);
    }

    public function test_step_five_completes_onboarding(): void
    {
        // Step 5 is now the final step: a valid tag completes onboarding, applies a
        // default template, and sends the welcome email.
        Mail::fake();
        $template = Template::factory()->create(['is_active' => true]);
        $user = User::factory()->create(['onboarding_step' => 4, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);
        $style = Tag::create(['name' => ['en' => 'Minimal'], 'slug' => 'minimal', 'category' => 'style']);
        $vibe = Tag::create(['name' => ['en' => 'Cozy'], 'slug' => 'cozy', 'category' => 'vibe']);

        // Step 5 requires one vibe AND one style tag.
        $response = $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 5, 'tag_ids' => [$style->id, $vibe->id]]);

        $response->assertOk()->assertJson(['completed' => true]);

        $user->refresh();
        $this->assertNotNull($user->onboarding_completed_at);
        $this->assertSame($template->id, $user->restaurant->template_id);
        Mail::assertSent(WelcomeRestaurantOwner::class);
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
