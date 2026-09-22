<?php

namespace Tests\Feature\Console;

use App\Models\Restaurant;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
        $restaurant->statistics()->create(['session_id' => 'old', 'viewed_at' => now()->subMonths(6)->subMinute()]);
        $restaurant->statistics()->create(['session_id' => 'edge', 'viewed_at' => now()->subMonths(6)->addMinute()]);
        $restaurant->statistics()->create(['session_id' => 'new', 'viewed_at' => now()]);

        $this->artisan('stats:rollup')->assertSuccessful()->expectsOutputToContain('Pruned 1');

        $this->assertDatabaseMissing('menu_sessions', ['session_id' => 'old']);
        $this->assertDatabaseHas('menu_sessions', ['session_id' => 'edge']);
        $this->assertDatabaseHas('menu_sessions', ['session_id' => 'new']);
    }

    public function test_a_custom_retention_can_be_passed(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->statistics()->create(['session_id' => 'two-months', 'viewed_at' => now()->subMonths(2)]);

        $this->artisan('stats:rollup', ['--prune-months' => 1])->assertSuccessful();

        $this->assertDatabaseCount('menu_sessions', 0);
    }

    public function test_zero_retention_disables_pruning(): void
    {
        $restaurant = Restaurant::factory()->create();
        $restaurant->statistics()->create(['session_id' => 'ancient', 'viewed_at' => now()->subYears(3)]);

        $this->artisan('stats:rollup', ['--prune-months' => 0])->assertSuccessful();

        $this->assertDatabaseCount('menu_sessions', 1);
    }

    public function test_rollup_on_an_empty_table_is_fine(): void
    {
        $this->artisan('stats:rollup')->assertSuccessful()->expectsOutputToContain('Pruned 0');
    }
}
