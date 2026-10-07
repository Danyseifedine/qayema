<?php

namespace Tests\Feature\Api;

use App\Models\Order;
use App\Models\PreviousSlug;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * Changing the menu's link (PUT /api/restaurant/slug): the old one keeps
 * forwarding, so printed QR codes and shared links never break.
 */
class RestaurantSlugTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function change(string $slug, $owner = null): \Illuminate\Testing\TestResponse
    {
        $owner ??= $this->published(['slug' => 'cedar']);

        return $this->actingAs($owner->user)->putJson(route('api.restaurant.slug'), ['slug' => $slug]);
    }

    public function test_it_requires_authentication(): void
    {
        $this->putJson(route('api.restaurant.slug'), ['slug' => 'new'])->assertUnauthorized();
    }

    public function test_the_menu_moves_and_the_old_link_forwards_with_its_qr_mark(): void
    {
        $shop = $this->published(['slug' => 'cedar']);

        $this->change('cedar-and-salt', $shop)
            ->assertOk()
            ->assertJsonPath('data.slug', 'cedar-and-salt');

        $this->get('/cedar-and-salt')->assertOk();
        // A printed QR code still lands on the menu, and still counts as a scan.
        $this->get('/cedar?qr=1')->assertStatus(301)->assertRedirect('/cedar-and-salt?qr=1');
        $this->assertSame(['cedar'], $shop->previousSlugs()->pluck('slug')->all());
    }

    public function test_every_page_under_the_old_link_forwards(): void
    {
        $shop = $this->published(['slug' => 'cedar']);
        $token = Str::random(40);
        Order::factory()->inMenu()->create(['restaurant_id' => $shop->id, 'tracking_token' => $token]);
        $this->change('cedar-and-salt', $shop)->assertOk();

        $this->get("/cedar/order/{$token}?lang=ar")->assertRedirect("/cedar-and-salt/order/{$token}?lang=ar");
    }

    /** A form posted from a page opened before the change is not forwarded. */
    public function test_only_a_visit_is_forwarded(): void
    {
        $shop = $this->published(['slug' => 'cedar']);
        $this->change('cedar-and-salt', $shop)->assertOk();

        $this->postJson('/cedar/events', ['events' => []])->assertNotFound();
    }

    public function test_the_link_is_written_cleanly(): void
    {
        $this->change('  Cedar & Salt Beirut ')
            ->assertOk()
            ->assertJsonPath('data.slug', 'cedar-salt-beirut');
    }

    public function test_a_link_that_cannot_be_used_is_refused(): void
    {
        $other = $this->published(['slug' => 'olive']);
        PreviousSlug::factory()->create(['restaurant_id' => $other->id, 'slug' => 'olive-tree']);
        $shop = $this->published(['slug' => 'cedar']);

        foreach (['olive', 'olive-tree', 'admin', 'pricing'] as $taken) {
            $this->change($taken, $shop)
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['slug' => 'That link is already taken. Try another one.']);
        }

        $this->change('x', $shop)->assertJsonValidationErrors(['slug']);
        $this->assertSame('cedar', $shop->fresh()->slug);
    }

    /** A restaurant may go back to a link it had: it is its own. */
    public function test_a_former_link_can_be_taken_back(): void
    {
        $shop = $this->published(['slug' => 'cedar']);
        $this->change('cedar-and-salt', $shop)->assertOk();

        $this->change('cedar', $shop)->assertOk()->assertJsonPath('data.slug', 'cedar');

        $this->assertSame(['cedar-and-salt'], $shop->previousSlugs()->pluck('slug')->all());
        $this->get('/cedar')->assertOk();
        $this->get('/cedar-and-salt')->assertRedirect('/cedar');
    }

    public function test_a_former_link_is_never_offered_to_someone_new(): void
    {
        $shop = $this->published(['slug' => 'cedar']);
        $this->change('cedar-and-salt', $shop)->assertOk();

        $this->actingAs($this->owner(['slug' => 'newcomer'])->user)
            ->getJson(route('onboarding.check-slug', ['slug' => 'cedar']))
            ->assertJsonPath('available', false);
    }

    public function test_a_switched_off_restaurants_old_link_leads_nowhere(): void
    {
        $shop = $this->published(['slug' => 'cedar']);
        $this->change('cedar-and-salt', $shop)->assertOk();
        $shop->fresh()->update(['is_active' => false]);

        $this->get('/cedar')->assertNotFound();
    }
}
