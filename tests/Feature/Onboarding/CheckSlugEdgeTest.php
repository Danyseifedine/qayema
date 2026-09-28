<?php

namespace Tests\Feature\Onboarding;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/** GET /onboarding/check-slug with input the wizard never sends. */
class CheckSlugEdgeTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** A slug sent as a list cannot be read as text; the check answers "not available" instead of a 500. */
    public function test_a_slug_sent_as_a_list_is_unavailable_not_an_error(): void
    {
        $this->actingAs($this->userWithoutRestaurant())
            ->getJson(route('onboarding.check-slug').'?slug[]=aran')
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_no_slug_at_all_is_too_short(): void
    {
        $this->actingAs($this->userWithoutRestaurant())
            ->getJson(route('onboarding.check-slug'))
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_a_slug_that_slugs_to_nothing_is_too_short(): void
    {
        $this->actingAs($this->userWithoutRestaurant())
            ->getJson(route('onboarding.check-slug', ['slug' => '!!! ???']))
            ->assertOk()
            ->assertExactJson(['available' => false]);
    }

    public function test_a_guest_is_sent_to_sign_in(): void
    {
        $this->get(route('onboarding.check-slug', ['slug' => 'aran']))->assertRedirect(route('login'));
    }
}
