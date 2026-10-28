<?php

namespace Tests\Integration\Database;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * The indexes the busiest reads lean on: the order pulse read from one
 * index, a restaurant's orders over time by channel, and no index on `menu_sessions.session_id` alone (every read of it
 * names the restaurant, so the counting index serves it). Each migration also
 * goes back down cleanly.
 */
class HotIndexesTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<list<string>> */
    private function indexes(string $table): array
    {
        return array_map(fn (array $index): array => $index['columns'], Schema::getIndexes($table));
    }

    private function migration(string $name): Migration
    {
        return require database_path("migrations/{$name}.php");
    }

    public function test_orders_have_one_index_for_the_pulse_and_the_open_count(): void
    {
        $this->assertContains(['restaurant_id', 'channel', 'status', 'fulfilment', 'guest_updated_at'], $this->indexes('orders'));
        $this->assertNotContains(['restaurant_id', 'channel', 'status'], $this->indexes('orders'));
    }

    public function test_menu_sessions_have_no_index_on_the_visitor_alone(): void
    {
        $this->assertNotContains(['session_id'], $this->indexes('menu_sessions'));
        $this->assertContains(['restaurant_id', 'viewed_at', 'via_qr', 'session_id'], $this->indexes('menu_sessions'));
    }

    public function test_both_migrations_go_down_and_up_again(): void
    {
        $orders = $this->migration('2026_10_19_100002_index_orders_for_the_pulse');
        $sessions = $this->migration('2026_10_19_100001_drop_menu_sessions_session_id_index');

        $orders->down();
        $sessions->down();

        $this->assertContains(['restaurant_id', 'channel', 'status'], $this->indexes('orders'));
        $this->assertContains(['session_id'], $this->indexes('menu_sessions'));

        $sessions->up();
        $orders->up();

        $this->assertNotContains(['session_id'], $this->indexes('menu_sessions'));
        $this->assertContains(['restaurant_id', 'channel', 'status', 'fulfilment', 'guest_updated_at'], $this->indexes('orders'));
    }

    public function test_orders_over_time_are_read_by_channel_and_status_has_no_index_of_its_own(): void
    {
        $this->assertContains(['restaurant_id', 'channel', 'placed_at'], $this->indexes('orders'));
        $this->assertNotContains(['restaurant_id', 'placed_at'], $this->indexes('orders'));
        $this->assertNotContains(['restaurant_id', 'status'], $this->indexes('orders'));
    }

    public function test_the_order_indexes_go_down_and_up_again(): void
    {
        $migration = $this->migration('2026_10_28_000004_fit_the_order_indexes_to_their_reads');

        $migration->down();

        $this->assertContains(['restaurant_id', 'placed_at'], $this->indexes('orders'));
        $this->assertContains(['restaurant_id', 'status'], $this->indexes('orders'));
        $this->assertNotContains(['restaurant_id', 'channel', 'placed_at'], $this->indexes('orders'));

        $migration->up();

        $this->assertContains(['restaurant_id', 'channel', 'placed_at'], $this->indexes('orders'));
        $this->assertNotContains(['restaurant_id', 'status'], $this->indexes('orders'));
    }
}
