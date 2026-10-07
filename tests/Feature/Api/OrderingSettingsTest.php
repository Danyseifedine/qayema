<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * How guests send their orders, from the Features page: on WhatsApp, or in
 * the menu with delivery and/or pickup.
 */
class OrderingSettingsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    public function test_it_requires_authentication(): void
    {
        $this->putJson(route('api.features.ordering'), ['mode' => 'whatsapp', 'types' => ['pickup']])->assertUnauthorized();
    }

    public function test_an_owner_on_premium_moves_ordering_into_the_menu(): void
    {
        $owner = $this->ownerOn('premium');

        $this->actingAs($owner->user)
            ->putJson(route('api.features.ordering'), ['mode' => 'menu', 'types' => ['pickup', 'delivery']])
            ->assertOk()
            // Kept in a fixed order, whatever order they were ticked in.
            ->assertExactJson(['data' => ['mode' => 'menu', 'types' => ['delivery', 'pickup']]]);

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.ordering', ['mode' => 'menu', 'types' => ['delivery', 'pickup']]);
    }

    public function test_pickup_only(): void
    {
        $owner = $this->ownerOn('premium');

        $this->actingAs($owner->user)
            ->putJson(route('api.features.ordering'), ['mode' => 'menu', 'types' => ['pickup']])
            ->assertOk();

        $this->assertSame(['pickup'], $owner->fresh()->orderTypes());
    }

    public function test_the_menu_needs_the_package_flag(): void
    {
        $owner = $this->ownerOn('pro');

        $this->actingAs($owner->user)
            ->putJson(route('api.features.ordering'), ['mode' => 'menu', 'types' => ['delivery']])
            ->assertForbidden();

        $this->assertSame('whatsapp', $owner->fresh()->order_mode);
    }

    public function test_whatsapp_is_always_allowed(): void
    {
        $this->defaultPackageIncludes(Feature::Ordering);
        $owner = $this->owner(['order_mode' => 'menu']);

        $this->actingAs($owner->user)
            ->putJson(route('api.features.ordering'), ['mode' => 'whatsapp', 'types' => ['delivery']])
            ->assertOk()
            ->assertJsonPath('data.mode', 'whatsapp');
    }

    public function test_at_least_one_kind_of_order_stays_on(): void
    {
        $owner = $this->ownerOn('premium');

        $this->actingAs($owner->user)
            ->putJson(route('api.features.ordering'), ['mode' => 'menu', 'types' => []])
            ->assertJsonValidationErrors(['types' => 'Keep delivery or pickup on.']);
        // Ordering at the table is its own feature, not one of these.
        $this->actingAs($owner->user)
            ->putJson(route('api.features.ordering'), ['mode' => 'menu', 'types' => ['dine_in']])
            ->assertJsonValidationErrors(['types.0']);
        $this->actingAs($owner->user)
            ->putJson(route('api.features.ordering'), ['mode' => 'carrier_pigeon', 'types' => ['pickup']])
            ->assertJsonValidationErrors(['mode']);
    }

    public function test_the_message_comes_in_arabic(): void
    {
        $owner = $this->ownerOn('premium');

        $this->actingAs($owner->user)
            ->withHeader('Accept-Language', 'ar')
            ->putJson(route('api.features.ordering'), ['mode' => 'menu', 'types' => []])
            ->assertJsonValidationErrors(['types' => 'أبقِ التوصيل أو الاستلام مفعّلًا.']);
    }

    public function test_an_account_without_a_restaurant_is_refused(): void
    {
        $user = \App\Models\User::factory()->create();

        $this->actingAs($user)
            ->putJson(route('api.features.ordering'), ['mode' => 'whatsapp', 'types' => ['pickup']])
            ->assertForbidden();
    }
}
