<?php

namespace Tests\Feature\Console;

use App\Models\Restaurant;
use App\Services\Analytics\MenuDays;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ScheduledTasksTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_rollup_is_scheduled_nightly(): void
    {
        $events = collect(app(Schedule::class)->events())->map(fn ($e) => [$e->command, $e->expression]);

        $rollup = $events->first(fn ($e) => str_contains($e[0], 'stats:rollup'));

        $this->assertNotNull($rollup, 'stats:rollup is not on the schedule');
        $this->assertSame('10 3 * * *', $rollup[1]);
    }

    public function test_rollup_prunes_exactly_past_the_retention_window(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->menuSessions()->create(['session_id' => 'old', 'viewed_at' => now()->subMonths(6)->subMinute()]);
        $restaurant->menuSessions()->create(['session_id' => 'edge', 'viewed_at' => now()->subMonths(6)->addMinute()]);
        $restaurant->menuSessions()->create(['session_id' => 'new', 'viewed_at' => now()]);

        $this->artisan('stats:rollup')->assertSuccessful()->expectsOutputToContain('Pruned 1');

        $this->assertDatabaseMissing('menu_sessions', ['session_id' => 'old']);
        $this->assertDatabaseHas('menu_sessions', ['session_id' => 'edge']);
        $this->assertDatabaseHas('menu_sessions', ['session_id' => 'new']);
    }

    public function test_a_custom_retention_can_be_passed(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->menuSessions()->create(['session_id' => 'two-months', 'viewed_at' => now()->subMonths(2)]);

        $this->artisan('stats:rollup', ['--prune-months' => 1])->assertSuccessful();

        $this->assertDatabaseCount('menu_sessions', 0);
    }

    public function test_zero_retention_disables_pruning(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->menuSessions()->create(['session_id' => 'ancient', 'viewed_at' => now()->subYears(3)]);

        $this->artisan('stats:rollup', ['--prune-months' => 0])->assertSuccessful();

        $this->assertDatabaseCount('menu_sessions', 1);
    }

    public function test_rollup_on_an_empty_table_is_fine(): void
    {
        $this->artisan('stats:rollup')->assertSuccessful()->expectsOutputToContain('Pruned 0');
    }

    public function test_every_finished_day_is_added_up_before_its_raw_rows_go(): void
    {
        $restaurant = Restaurant::factory()->create(['timezone' => 'UTC']);
        $restaurant->menuSessions()->create(['session_id' => 'old', 'viewed_at' => now()->subMonths(7), 'via_qr' => true]);
        $restaurant->menuEvents()->create(['session_id' => 'old', 'type' => 'whatsapp', 'occurred_at' => now()->subMonths(7)]);
        $restaurant->menuSessions()->create(['session_id' => 'new', 'viewed_at' => now()->subDay()]);

        $this->artisan('stats:rollup')->assertSuccessful()->expectsOutputToContain('Added up 2 days.')->expectsOutputToContain('Pruned 1');

        $this->assertDatabaseMissing('menu_sessions', ['session_id' => 'old']);
        $this->assertDatabaseCount('menu_events', 0);
        $old = $restaurant->menuDays()->orderBy('day')->first();
        $this->assertSame(now()->subMonths(7)->toDateString(), $old->day->toDateString());
        $this->assertSame(1, $old->views);
        $this->assertSame(1, $old->qr_scans);
        $this->assertSame(['whatsapp' => 1], $old->actions);
    }

    public function test_if_adding_up_fails_nothing_is_deleted(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->menuSessions()->create(['session_id' => 'old', 'viewed_at' => now()->subMonths(7)]);
        $this->mock(MenuDays::class)->shouldReceive('addUpMissing')->andThrow(new \RuntimeException('Database went away'));

        try {
            $this->artisan('stats:rollup');
            $this->fail('The rollup carried on past a failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Database went away', $exception->getMessage());
        }

        $this->assertDatabaseHas('menu_sessions', ['session_id' => 'old']);
    }

    public function test_the_retention_defaults_to_the_one_menu_days_keeps(): void
    {
        $option = Artisan::all()['stats:rollup']->getDefinition()->getOption('prune-months');

        $this->assertSame((string) MenuDays::RAW_MONTHS, (string) $option->getDefault());
    }

    /** A restaurant that cannot be added up keeps its raw rows; the rest are pruned as usual. */
    public function test_one_restaurant_that_fails_keeps_its_rows_and_the_rest_are_pruned(): void
    {
        $broken = Restaurant::factory()->create();
        $broken->menuSessions()->create(['session_id' => 'broken', 'viewed_at' => now()->subMonths(7)]);
        $fine = Restaurant::factory()->create();
        $fine->menuSessions()->create(['session_id' => 'fine', 'viewed_at' => now()->subMonths(7)]);
        $this->mock(MenuDays::class)->shouldReceive('addUpMissing')->andReturn(['written' => 0, 'failed' => [$broken->id]]);

        $this->artisan('stats:rollup')
            ->assertSuccessful()
            ->expectsOutputToContain("Could not add up restaurant(s) {$broken->id}")
            ->expectsOutputToContain('Pruned 1 raw menu_sessions');

        $this->assertDatabaseHas('menu_sessions', ['session_id' => 'broken']);
        $this->assertDatabaseMissing('menu_sessions', ['session_id' => 'fine']);
    }

    public function test_the_prune_deletes_a_batch_at_a_time(): void
    {
        $restaurant = Restaurant::factory()->create();
        foreach (range(1, 5) as $i) {
            $restaurant->menuSessions()->create(['session_id' => "old-{$i}", 'viewed_at' => now()->subMonths(7)]);
        }
        $restaurant->menuSessions()->create(['session_id' => 'new', 'viewed_at' => now()]);
        $deletes = 0;
        DB::listen(function ($query) use (&$deletes): void {
            $deletes += (int) str_starts_with(strtolower($query->sql), 'delete from "menu_sessions"');
        });

        $this->artisan('stats:rollup', ['--batch' => 2])->assertSuccessful()->expectsOutputToContain('Pruned 5 raw menu_sessions');

        // 2, 2, 1, then one that finds nothing left.
        $this->assertSame(4, $deletes);
        $this->assertDatabaseCount('menu_sessions', 1);
    }
}
