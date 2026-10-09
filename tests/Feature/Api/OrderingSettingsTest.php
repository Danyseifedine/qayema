<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * How guests send their orders, from the Features page: on WhatsApp, or in
 * the menu with delivery and/or pickup; and orders at the table, apart.
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
            ->assertJsonPath('data.restaurant.ordering.mode', 'menu')
            ->assertJsonPath('data.restaurant.ordering.types', ['delivery', 'pickup'])
            // Orders at the table keep their own way in.
            ->assertJsonPath('data.restaurant.ordering.dine_in', 'menu');
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
        $this->actingAs($user)
            ->putJson(route('api.features.dine-in'), ['mode' => 'menu'])
            ->assertForbidden();
    }

    public function test_table_orders_move_to_whatsapp_and_back_without_touching_delivery(): void
    {
        $owner = $this->ownerOn('premium');
        $owner->update(['order_mode' => 'menu', 'order_types' => ['pickup'], 'country_code' => 'LB', 'phone' => '70123456']);

        $this->actingAs($owner->user)
            ->putJson(route('api.features.dine-in'), ['mode' => 'whatsapp'])
            ->assertOk()
            ->assertExactJson(['data' => ['dine_in' => 'whatsapp']]);

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.ordering.dine_in', 'whatsapp')
            ->assertJsonPath('data.restaurant.ordering.whatsapp_number', true)
            ->assertJsonPath('data.restaurant.ordering.mode', 'menu')
            ->assertJsonPath('data.restaurant.ordering.types', ['pickup']);

        $this->actingAs($owner->user)->putJson(route('api.features.dine-in'), ['mode' => 'menu'])->assertOk();
        $this->assertSame('menu', $owner->fresh()->dine_in_mode);
    }

    public function test_whatsapp_for_table_orders_needs_a_number(): void
    {
        $owner = $this->ownerOn('premium');
        $owner->update(['phone' => null]);

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.ordering.whatsapp_number', false);

        $this->actingAs($owner->user)
            ->withHeader('Accept-Language', 'ar')
            ->putJson(route('api.features.dine-in'), ['mode' => 'whatsapp'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['mode' => 'أضف رقم واتساب في صفحة المطعم أولاً.']);

        $this->actingAs($owner->user)
            ->putJson(route('api.features.dine-in'), ['mode' => 'carrier_pigeon'])
            ->assertJsonValidationErrors(['mode']);
        $this->assertSame('menu', $owner->fresh()->dine_in_mode);
    }

    public function test_choosing_how_table_orders_come_in_requires_authentication(): void
    {
        $this->putJson(route('api.features.dine-in'), ['mode' => 'menu'])->assertUnauthorized();
    }

    public function test_what_a_whatsapp_order_asks_for_is_saved_whole_and_starts_off(): void
    {
        $owner = $this->ownerOn('premium');
        $off = ['away' => ['name' => 'off', 'phone' => 'off', 'address' => 'off'], 'table' => ['name' => 'off', 'phone' => 'off']];

        $this->actingAs($owner->user)
            ->getJson(route('api.user'))
            ->assertJsonPath('data.restaurant.ordering.whatsapp_fields', $off);

        $asks = ['away' => ['name' => 'required', 'phone' => 'optional', 'address' => 'required'], 'table' => ['name' => 'optional', 'phone' => 'off']];
        $this->actingAs($owner->user)
            ->putJson(route('api.features.whatsapp-fields'), $asks)
            ->assertOk()
            ->assertExactJson(['data' => $asks]);

        $this->assertSame($asks, $owner->fresh()->whatsappAsks());
    }

    public function test_whatsapp_fields_take_only_off_optional_or_required_for_every_field(): void
    {
        $owner = $this->ownerOn('premium');

        $this->actingAs($owner->user)
            ->putJson(route('api.features.whatsapp-fields'), [
                'away' => ['name' => 'always', 'phone' => 'off'],
                'table' => ['name' => 'off', 'phone' => 'off'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['away.name', 'away.address']);

        $this->assertNull($owner->fresh()->whatsapp_fields);
    }

    public function test_whatsapp_fields_need_a_signed_in_owner_with_a_restaurant(): void
    {
        $this->putJson(route('api.features.whatsapp-fields'), [])->assertUnauthorized();

        $this->actingAs(\App\Models\User::factory()->create())
            ->putJson(route('api.features.whatsapp-fields'), [])
            ->assertForbidden();
    }
}
