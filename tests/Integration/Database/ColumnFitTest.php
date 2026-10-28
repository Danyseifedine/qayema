<?php

namespace Tests\Integration\Database;

use App\Models\BlockedIp;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * The columns widened to hold what the requests accept, and the picked dates
 * made `datetime`, go down and up again with their values kept. SQLite keeps
 * no lengths, so the widths themselves are only MySQL's to enforce.
 *
 * Only rows with no parent are checked: SQLite rebuilds a table to change a
 * column, and inside the test's transaction it cannot switch the foreign
 * keys off first, so rebuilding `restaurants` cascades to its children.
 */
class ColumnFitTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    private function migration(string $name): Migration
    {
        return require database_path("migrations/{$name}.php");
    }

    public function test_the_widened_columns_go_down_and_up_with_their_values(): void
    {
        $restaurant = $this->owner(['google_maps_url' => 'https://maps.app.goo.gl/'.str_repeat('a', 2000)]);
        $migration = $this->migration('2026_10_28_000002_widen_columns_to_hold_what_is_accepted');

        $migration->down();
        $migration->up();

        $this->assertSame(2024, strlen((string) DB::table('restaurants')->where('id', $restaurant->id)->value('google_maps_url')));
    }

    public function test_dates_past_2038_are_kept_down_and_up(): void
    {
        $restaurant = $this->owner();
        DB::table('restaurants')->where('id', $restaurant->id)->update(['package_ends_at' => '2040-01-01 00:00:00']);
        $block = BlockedIp::factory()->create(['expires_at' => '2045-06-01 12:30:00']);
        $migration = $this->migration('2026_10_28_000003_let_chosen_dates_run_past_2038');

        $migration->down();
        $migration->up();

        $this->assertSame('2040-01-01 00:00:00', $restaurant->fresh()->package_ends_at->toDateTimeString());
        $this->assertSame('2045-06-01 12:30:00', $block->fresh()->expires_at->toDateTimeString());
    }
}
