<?php

namespace Tests\Feature\Api;

use App\Enums\Feature;
use App\Enums\Fulfilment;
use App\Enums\OrderChannel;
use App\Models\DiningTable;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The dashboard's Tables page: each table has its own QR code for ordering
 * from the seat, which comes with ordering in the menu.
 */
class DiningTablesTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->defaultPackageIncludes(Feature::DineIn);
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson(route('api.tables.index'))->assertUnauthorized();
    }

    public function test_tables_come_with_ordering_at_the_table(): void
    {
        $this->defaultPackageSets(Feature::DineIn, 0);
        $owner = $this->owner();

        $this->actingAs($owner->user)->getJson(route('api.tables.index'))->assertForbidden();
        $this->actingAs($owner->user)->postJson(route('api.tables.store'), ['names' => ['Table 1']])->assertForbidden();
    }

    public function test_an_owner_adds_many_tables_at_once_each_with_its_own_code(): void
    {
        $owner = $this->owner(['slug' => 'olive']);

        $response = $this->actingAs($owner->user)
            ->postJson(route('api.tables.store'), ['names' => ['Table 1', ' Table 2 ', 'Terrace']])
            ->assertCreated()
            ->assertJsonPath('data.1.name', 'Table 2');

        $codes = array_column($response->json('data'), 'code');
        $this->assertCount(3, array_unique($codes));
        $this->assertSame(10, strlen($codes[0]));
        $this->assertSame(config('app.url').'/olive?table='.$codes[0].'&qr=1', $response->json('data.0.url'));

        $this->actingAs($owner->user)
            ->getJson(route('api.tables.index'))
            ->assertOk()
            ->assertJsonPath('data.*.name', ['Table 1', 'Table 2', 'Terrace'])
            ->assertJsonPath('meta.limit', 300);
    }

    public function test_more_tables_are_added_after_the_last_one(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner->user)->postJson(route('api.tables.store'), ['names' => ['B']])->assertCreated();
        $this->actingAs($owner->user)->postJson(route('api.tables.store'), ['names' => ['A']])->assertCreated();

        $this->actingAs($owner->user)
            ->getJson(route('api.tables.index'))
            ->assertJsonPath('data.*.name', ['B', 'A']);
    }

    public function test_every_table_has_its_own_name(): void
    {
        $owner = $this->owner();
        DiningTable::factory()->for($owner)->create(['name' => 'Table 1']);

        $this->actingAs($owner->user)
            ->postJson(route('api.tables.store'), ['names' => ['Table 1']])
            ->assertJsonValidationErrors(['names.0' => 'You already have a table with this name.']);

        $this->actingAs($owner->user)
            ->postJson(route('api.tables.store'), ['names' => ['Bar', 'bar']])
            ->assertJsonValidationErrors(['names.0' => 'Each table needs its own name.']);

        $this->actingAs($owner->user)
            ->postJson(route('api.tables.store'), ['names' => ['']])
            ->assertJsonValidationErrors(['names.0' => 'Give the table a name.']);

        // Another restaurant's tables are no clash.
        $other = $this->owner();
        $this->actingAs($other->user)->postJson(route('api.tables.store'), ['names' => ['Table 1']])->assertCreated();
    }

    public function test_a_restaurant_has_a_most_tables(): void
    {
        config(['menu.tables.max' => 3]);
        $owner = $this->owner();
        DiningTable::factory()->for($owner)->count(2)->create();

        $this->actingAs($owner->user)
            ->postJson(route('api.tables.store'), ['names' => ['X', 'Y']])
            ->assertJsonValidationErrors(['names' => 'A restaurant can have up to 3 tables.']);

        $this->assertSame(2, $owner->diningTables()->count());
    }

    public function test_renaming_a_table_keeps_its_code(): void
    {
        $owner = $this->owner();
        $table = DiningTable::factory()->for($owner)->create(['name' => 'Table 1']);

        $this->actingAs($owner->user)
            ->patchJson(route('api.tables.update', $table->id), ['name' => 'Window'])
            ->assertOk()
            ->assertJsonPath('data.name', 'Window')
            ->assertJsonPath('data.code', $table->code);

        // Its own name is not a clash with itself.
        $this->actingAs($owner->user)
            ->patchJson(route('api.tables.update', $table->id), ['name' => 'Window'])
            ->assertOk();
    }

    public function test_a_new_code_retires_the_printed_one(): void
    {
        $owner = $this->owner();
        $table = DiningTable::factory()->for($owner)->create();
        $old = $table->code;

        $code = $this->actingAs($owner->user)
            ->postJson(route('api.tables.new-code', $table->id))
            ->assertOk()
            ->json('data.code');

        $this->assertNotSame($old, $code);
        $this->assertSame($code, $table->fresh()->code);
    }

    public function test_a_removed_table_leaves_its_orders_saying_where_they_went(): void
    {
        $owner = $this->owner();
        $table = DiningTable::factory()->for($owner)->create(['name' => 'Table 7']);
        $order = Order::factory()->for($owner)->inMenu(Fulfilment::DineIn)->create([
            'channel' => OrderChannel::Menu, 'table_id' => $table->id, 'table_name' => 'Table 7',
        ]);

        $this->actingAs($owner->user)->deleteJson(route('api.tables.destroy', $table->id))->assertNoContent();

        $this->assertNull($order->fresh()->table_id);
        $this->assertSame('Table 7', $order->fresh()->table_name);
    }

    public function test_another_restaurants_table_is_not_found(): void
    {
        $owner = $this->owner();
        $theirs = DiningTable::factory()->create();

        $this->actingAs($owner->user)->patchJson(route('api.tables.update', $theirs->id), ['name' => 'Mine'])->assertNotFound();
        $this->actingAs($owner->user)->postJson(route('api.tables.new-code', $theirs->id))->assertNotFound();
        $this->actingAs($owner->user)->deleteJson(route('api.tables.destroy', $theirs->id))->assertNotFound();
        $this->assertNotNull($theirs->fresh());
    }

    public function test_the_list_says_whether_guests_can_order_to_the_tables_now(): void
    {
        // Ordering at the table is its own feature: delivery and pickup
        // going to WhatsApp changes nothing about it.
        $owner = $this->owner(['order_mode' => 'whatsapp']);

        $this->actingAs($owner->user)->getJson(route('api.tables.index'))->assertJsonPath('meta.takes_orders', true);

        // A fresh user: the test's own copy would keep the old restaurant
        // loaded, as no real request does.
        $owner->update(['switched_off' => ['dine_in']]);
        $this->actingAs($owner->user->fresh())->getJson(route('api.tables.index'))->assertJsonPath('meta.takes_orders', false);
    }
}
