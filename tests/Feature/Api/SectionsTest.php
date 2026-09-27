<?php

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class SectionsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_every_section_shows_until_the_owner_hides_one(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.hidden_sections', []);
    }

    public function test_an_owner_can_hide_and_show_sections(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->putJson(route('api.sections.update'), ['hidden' => ['orders', 'analytics']])
            ->assertOk()
            ->assertJsonPath('data.hidden', ['analytics', 'orders']);

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.hidden_sections', ['analytics', 'orders']);

        $this->actingAs($owner->user)
            ->putJson(route('api.sections.update'), ['hidden' => []])
            ->assertOk()
            ->assertJsonPath('data.hidden', []);
    }

    public function test_core_sections_cannot_be_hidden(): void
    {
        $owner = $this->owner();

        foreach (['overview', 'dishes', 'settings', 'package', 'nonsense'] as $section) {
            $this->actingAs($owner->user)
                ->putJson(route('api.sections.update'), ['hidden' => [$section]])
                ->assertStatus(422, $section)
                ->assertJsonValidationErrors('hidden.0');
        }
    }

    public function test_the_list_must_be_sent(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)
            ->putJson(route('api.sections.update'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors('hidden');
    }

    public function test_hiding_orders_does_not_stop_the_menu_taking_them(): void
    {
        $owner = $this->owner();

        $this->actingAs($owner->user)->putJson(route('api.sections.update'), ['hidden' => ['orders']])->assertOk();

        $this->actingAs($owner->user)->getJson(route('api.orders.index'))->assertOk();
    }

    public function test_a_guest_cannot_change_sections(): void
    {
        $this->putJson(route('api.sections.update'), ['hidden' => []])->assertUnauthorized();
    }
}
