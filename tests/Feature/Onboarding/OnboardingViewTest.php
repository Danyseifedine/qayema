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

    public function test_the_first_step_only_asks_for_what_it_shows(): void
    {
        // Step 1 has a name and a menu link; it once also promised a
        // language choice that has no control.
        $user = User::factory()->create(['onboarding_step' => 0, 'onboarding_completed_at' => null]);

        $this->actingAs($user)->get(route('onboarding'))
            ->assertOk()
            ->assertSee(__('owner.onboarding.step1_desc'))
            ->assertDontSee('Choose the language');
    }

    public function test_a_field_keeps_its_hint_while_it_shows_an_error(): void
    {
        // The hint and the error sit together under the control; an error
        // used to hide the hint (x-show="!errors.name").
        $user = User::factory()->create(['onboarding_step' => 0, 'onboarding_completed_at' => null]);

        $html = $this->actingAs($user)->get(route('onboarding'))->assertOk()->getContent();

        foreach (['name', 'phone', 'currency', 'logo'] as $field) {
            $this->assertStringNotContainsString('x-show="!errors.'.$field.'"', $html);
        }
        $this->assertSame(5, substr_count($html, 'class="ui-helps"'));
    }

    public function test_the_onboarding_page_renders_mid_flow(): void
    {
        $user = User::factory()->create(['onboarding_step' => 2, 'onboarding_completed_at' => null]);
        Restaurant::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->get(route('onboarding'))->assertOk();
    }

    public function test_leaving_an_unchanged_menu_link_does_not_check_it_again(): void
    {
        // Clicking Continue blurs the link field. Re-checking an unchanged
        // link there turned a settled "available"/"taken" back into
        // "checking", and the step refused to advance with "Checking
        // availability…" (found by e2e/specs/onboarding.spec.ts).
        $user = User::factory()->create(['onboarding_step' => 0, 'onboarding_completed_at' => null]);

        $this->actingAs($user)->get(route('onboarding'))
            ->assertOk()
            ->assertSee("const trimmed = this.s1.slug.replace(/-+$/, '');", false)
            ->assertSee('if (trimmed === this.s1.slug) return;', false);
    }
}
