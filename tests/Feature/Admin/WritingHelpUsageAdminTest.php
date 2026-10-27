<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Widgets\WritingHelpUsage;
use App\Models\Package;
use App\Models\WritingHelpDay;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\Support\CreatesOwners;
use Tests\TestCase;

/**
 * What the AI help was used for and cost, as an admin reads it at /admin:
 * on a restaurant's page, the dashboard's card and the restaurants table.
 */
class WritingHelpUsageAdminTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.writing_help.provider' => 'anthropic',
            'ai.providers.anthropic.key' => 'test-key',
        ]);
        $this->actingAs($this->admin());
    }

    public function test_the_restaurant_page_says_how_much_ai_help_it_used(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 12:00', 'UTC'));
        $restaurant = $this->ownerOn('pro', ['timezone' => 'UTC']);
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-10-11', 'uses' => 3, 'input_tokens' => 1200, 'output_tokens' => 100, 'cost' => '0.150000']);
        WritingHelpDay::factory()->for($restaurant)->create(['day' => '2026-10-01', 'uses' => 38, 'input_tokens' => 50000, 'output_tokens' => 1000, 'cost' => '0.090000']);

        Livewire::test(EditRestaurant::class, ['record' => $restaurant->id])
            ->assertSee('AI writing help')
            ->assertSee('3 today, 41 in the last 30 days (52,300 tokens, $0.24)');
    }

    public function test_a_restaurant_that_never_used_it_says_so(): void
    {
        Livewire::test(EditRestaurant::class, ['record' => $this->owner()->id])
            ->assertSee('Not used in the last 30 days');
    }

    public function test_the_dashboard_shows_everyones_use_over_thirty_days(): void
    {
        config(['services.writing_help.provider' => 'anthropic', 'services.writing_help.model' => 'claude-haiku-5-5']);
        WritingHelpDay::factory()->for($this->owner())->create(['uses' => 4, 'input_tokens' => 1000, 'output_tokens' => 200, 'cost' => '1.250000']);
        WritingHelpDay::factory()->for($this->owner())->create(['day' => now()->subDays(3), 'uses' => 2, 'input_tokens' => 500, 'output_tokens' => 100, 'cost' => '0.004000']);

        Livewire::test(WritingHelpUsage::class)
            ->assertSee('AI writing help, last 30 days')
            // What it cost, today apart, and at what price.
            ->assertSeeInOrder(['AI cost', '$1.25', '$1.25 today', 'Uses', '6', 'Tokens', '1,800', 'Restaurants', '2'])
            ->assertSee('claude-haiku-5-5: $0.10 in, $0.50 out per million tokens');
    }

    public function test_the_dashboard_carries_the_ai_card(): void
    {
        $this->get(route('filament.admin.pages.dashboard'))
            ->assertOk()
            ->assertSeeLivewire(WritingHelpUsage::class);
    }

    public function test_the_restaurants_table_shows_each_ones_ai_uses_over_thirty_days(): void
    {
        $this->travelTo(Carbon::parse('2026-10-11 12:00', 'UTC'));
        $busy = $this->owner();
        $quiet = $this->owner();
        $never = $this->owner();
        WritingHelpDay::factory()->for($busy)->create(['day' => '2026-10-11', 'uses' => 30, 'input_tokens' => 40000, 'output_tokens' => 2000]);
        WritingHelpDay::factory()->for($busy)->create(['day' => '2026-09-25', 'uses' => 12, 'input_tokens' => 9000, 'output_tokens' => 500]);
        // Older than thirty days: out of the column.
        WritingHelpDay::factory()->for($busy)->create(['day' => '2026-09-01', 'uses' => 500]);
        WritingHelpDay::factory()->for($quiet)->create(['day' => '2026-10-10', 'uses' => 2, 'input_tokens' => 800, 'output_tokens' => 60]);

        Livewire::test(ListRestaurants::class)
            ->toggleAllTableColumns()
            ->assertTableColumnExists('writing_help_uses')
            ->assertTableColumnStateSet('writing_help_uses', 42, $busy)
            ->assertTableColumnStateSet('writing_help_uses', 2, $quiet)
            ->assertTableColumnStateSet('writing_help_uses', 0, $never)
            ->assertSee('51,500 tokens')
            ->assertSee('860 tokens');
    }

    public function test_the_restaurants_table_shows_what_each_ones_ai_cost(): void
    {
        $busy = $this->owner();
        $never = $this->owner();
        WritingHelpDay::factory()->for($busy)->create(['uses' => 30, 'cost' => '0.420000']);
        WritingHelpDay::factory()->for($busy)->create(['day' => now()->subDays(40), 'uses' => 9, 'cost' => '9.000000']);

        Livewire::test(ListRestaurants::class)
            ->toggleAllTableColumns()
            ->assertTableColumnStateSet('writing_help_cost', 0.42, $busy)
            ->assertTableColumnStateSet('writing_help_cost', 0.0, $never)
            ->assertSee('$0.42')
            ->sortTable('writing_help_cost', 'desc')
            ->assertCanSeeTableRecords([$busy, $never], inOrder: true);
    }

    public function test_the_heaviest_ai_users_come_first_when_sorted(): void
    {
        $light = $this->owner();
        $heavy = $this->owner();
        $none = $this->owner();
        WritingHelpDay::factory()->for($light)->create(['uses' => 3]);
        WritingHelpDay::factory()->for($heavy)->create(['uses' => 90]);

        Livewire::test(ListRestaurants::class)
            ->sortTable('writing_help_uses', 'desc')
            ->assertCanSeeTableRecords([$heavy, $light, $none], inOrder: true)
            ->sortTable('writing_help_uses', 'asc')
            ->assertCanSeeTableRecords([$none, $light, $heavy], inOrder: true);
    }

    public function test_the_ai_column_adds_no_query_per_row(): void
    {
        $count = function (): int {
            DB::flushQueryLog();
            DB::enableQueryLog();
            Livewire::test(ListRestaurants::class);
            DB::disableQueryLog();

            return count(DB::getQueryLog());
        };
        WritingHelpDay::factory()->for($this->owner())->create();
        $few = $count();

        foreach (range(1, 6) as $n) {
            WritingHelpDay::factory()->for($this->owner())->create();
        }

        // As AdminListQueriesTest allows: the hidden dish limit's package, one a row.
        $this->assertLessThanOrEqual($few + 6, $count());
    }
}
