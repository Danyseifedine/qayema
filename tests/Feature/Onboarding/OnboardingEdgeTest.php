<?php

namespace Tests\Feature\Onboarding;

use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OnboardingEdgeTest extends TestCase
{
    use RefreshDatabase;

    private function fresh(): User
    {
        return User::factory()->create(['onboarding_step' => 0, 'onboarding_completed_at' => null]);
    }

    public function test_step_one_creates_the_restaurant_once_and_updates_it_on_revisit(): void
    {
        $user = $this->fresh();

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'First Name', 'slug' => 'first', 'default_locale' => 'en'])->assertOk();
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Second Name', 'slug' => 'second', 'default_locale' => 'en'])->assertOk();

        $this->assertSame(1, Restaurant::count());
        $this->assertSame('second', $user->fresh()->restaurant->slug);
        $this->assertSame('Second Name', $user->fresh()->restaurant->getTranslation('name', 'en'));
    }

    public function test_the_name_is_saved_in_english_and_arabic_is_the_second_language(): void
    {
        // English is every menu's main language, so the one name typed at
        // onboarding goes there; the menu still opens in the language chosen.
        $user = $this->fresh();

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'مطعمي', 'slug' => 'mine', 'default_locale' => 'ar'])->assertOk();

        $restaurant = $user->fresh()->restaurant;
        $this->assertSame('ar', $restaurant->default_locale);
        $this->assertSame('ar', $restaurant->second_locale);
        $this->assertSame('مطعمي', $restaurant->getTranslation('name', 'en', false));
    }

    public function test_running_step_one_again_keeps_the_other_languages_of_the_name(): void
    {
        $user = $this->fresh();
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Olive', 'slug' => 'mine', 'default_locale' => 'en'])->assertOk();
        $user->fresh()->restaurant->setTranslation('name', 'ar', 'زيتون')->save();

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Olive Bar', 'slug' => 'mine', 'default_locale' => 'en'])->assertOk();

        $restaurant = $user->fresh()->restaurant;
        $this->assertSame('Olive Bar', $restaurant->getTranslation('name', 'en', false));
        $this->assertSame('زيتون', $restaurant->getTranslation('name', 'ar', false));
    }

    public function test_a_slug_taken_by_someone_else_is_rejected_but_your_own_is_fine(): void
    {
        Restaurant::factory()->create(['slug' => 'taken']);
        $user = $this->fresh();

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Taken Place', 'slug' => 'taken'])->assertStatus(422)->assertJsonValidationErrors('slug');

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'My Place', 'slug' => 'mine'])->assertOk();
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'My Place', 'slug' => 'mine'])->assertOk();
    }

    public function test_check_slug_handles_garbage_and_boundaries(): void
    {
        $user = $this->fresh();
        Restaurant::factory()->create(['slug' => 'taken']);

        $this->actingAs($user)->getJson(route('onboarding.check-slug', ['slug' => 'taken']))->assertJsonPath('available', false);
        $this->actingAs($user)->getJson(route('onboarding.check-slug', ['slug' => 'Free Slug!']))->assertJsonPath('available', true)->assertJsonPath('slug', 'free-slug');
        $this->actingAs($user)->getJson(route('onboarding.check-slug', ['slug' => 'a']))->assertJsonPath('available', false);
        // Markup is not rejected, it is slugified into something harmless.
        $this->actingAs($user)->getJson(route('onboarding.check-slug', ['slug' => '<script>']))->assertOk()->assertJsonPath('slug', 'script')->assertJsonPath('available', true);
        $this->actingAs($user)->getJson(route('onboarding.check-slug'))->assertOk()->assertJsonPath('available', false);
    }

    public function test_a_reserved_word_cannot_become_a_slug(): void
    {
        $user = $this->fresh();

        // These would collide with real routes; the public menu route excludes
        // them, so an owner who picked one would have a dead link.
        foreach (['admin', 'api', 'up', 'contact'] as $reserved) {
            $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Some Place', 'slug' => $reserved])
                ->assertStatus(422, $reserved)->assertJsonValidationErrors('slug');
        }
    }

    public function test_the_saved_step_never_moves_backwards(): void
    {
        $user = $this->fresh();
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'X Place', 'slug' => 'x-slug']);
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 2, 'phone' => '+96170123456', 'currency' => 'USD']);
        $this->assertSame(2, $user->fresh()->onboarding_step);

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1, 'name' => 'Y Place', 'slug' => 'y-slug']);

        $this->assertSame(2, $user->fresh()->onboarding_step);
    }

    public function test_a_completed_owner_is_sent_to_the_dashboard_instead_of_the_wizard(): void
    {
        config(['app.dashboard_url' => 'https://dash.qayema.test']);

        $user = User::factory()->create(['onboarding_completed_at' => now(), 'onboarding_step' => 3]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        // Asserting the target, not just "a redirect": the bare assertion let
        // this send finished owners to the landing page unnoticed.
        $this->actingAs($user)
            ->get(route('onboarding'))
            ->assertRedirect('https://dash.qayema.test');
    }

    public function test_completing_twice_does_not_send_two_welcome_mails(): void
    {
        Mail::fake();
        $user = User::factory()->create(['onboarding_step' => 2, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);
        $key = '11111111-1111-1111-1111-111111111111';

        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 3, 'logo_key' => $key])->assertOk();
        $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 3, 'logo_key' => $key])->assertOk();

        Mail::assertQueued(\App\Mail\WelcomeRestaurantOwner::class, 1);
    }

    public function test_a_guest_cannot_advance(): void
    {
        $this->postJson(route('onboarding.advance'), ['_step' => 1])->assertUnauthorized();
    }

    public function test_the_wizard_is_rate_limited(): void
    {
        $user = $this->fresh();
        $status = null;

        for ($i = 0; $i < 130 && $status !== 429; $i++) {
            $status = $this->actingAs($user)->postJson(route('onboarding.advance'), ['_step' => 1])->getStatusCode();
        }

        $this->assertSame(429, $status);
    }
}
