<?php

namespace Tests\Feature\Admin;

use App\Filament\Admin\Widgets\PopularRestaurantsWidget;
use App\Filament\Admin\Widgets\RecentActivityWidget;
use App\Filament\Admin\Widgets\RestaurantStatsWidget;
use App\Filament\Admin\Widgets\TodayOverviewWidget;
use App\Filament\Admin\Widgets\VisitorStatsWidget;
use App\Models\Restaurant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreatesOwners;
use Tests\TestCase;

class AdminWidgetsTest extends TestCase
{
    use CreatesOwners, RefreshDatabase;

    /** @return array<string, array{0: class-string}> */
    public static function widgets(): array
    {
        return [
            'today overview' => [TodayOverviewWidget::class],
            'restaurant stats' => [RestaurantStatsWidget::class],
            'visitor stats' => [VisitorStatsWidget::class],
            'popular restaurants' => [PopularRestaurantsWidget::class],
            'recent activity' => [RecentActivityWidget::class],
        ];
    }

    /** @dataProvider widgets */
    public function test_every_widget_renders_on_an_empty_database(string $widget): void
    {
        $this->actingAs($this->admin());

        Livewire::test($widget)->assertOk();
    }

    /** @dataProvider widgets */
    public function test_every_widget_renders_with_data(string $widget): void
    {
        $restaurant = $this->published();
        $restaurant->statistics()->create(['session_id' => 'a', 'device_type' => 'mobile', 'via_qr' => true, 'viewed_at' => now()]);
        $restaurant->statistics()->create(['session_id' => 'b', 'device_type' => 'desktop', 'via_qr' => false, 'viewed_at' => now()->subDays(2)]);
        Restaurant::factory()->create();
        $this->actingAs($this->admin());

        Livewire::test($widget)->assertOk();
    }

    public function test_the_dashboard_page_shows_the_widgets(): void
    {
        // Widgets load lazily, so the page itself only carries their mounts.
        $this->actingAs($this->admin())->get('/admin')->assertOk()->assertSee('wire:snapshot', false);
    }

    public function test_visitor_stats_count_rows_and_qr_scans(): void
    {
        $restaurant = $this->published();
        $restaurant->statistics()->create(['session_id' => 'a', 'via_qr' => true, 'viewed_at' => now()]);
        $restaurant->statistics()->create(['session_id' => 'a', 'via_qr' => false, 'viewed_at' => now()]);
        $this->actingAs($this->admin());

        Livewire::test(VisitorStatsWidget::class)
            ->assertSee('Total Page Views')->assertSee('QR Scans')
            ->assertSeeInOrder(['Total Page Views', '2'])
            ->assertSeeInOrder(['Unique Sessions', '1']);
    }

    public function test_popular_restaurants_orders_by_views(): void
    {
        $quiet = $this->published(['name' => ['en' => 'Quiet Spot']]);
        $busy = $this->published(['name' => ['en' => 'Busy Spot'], 'slug' => 'busy']);
        foreach (range(1, 3) as $i) {
            $busy->statistics()->create(['session_id' => "s{$i}", 'viewed_at' => now()]);
        }
        $quiet->statistics()->create(['session_id' => 'q', 'viewed_at' => now()]);
        $this->actingAs($this->admin());

        Livewire::test(PopularRestaurantsWidget::class)->assertSeeInOrder(['Busy Spot', 'Quiet Spot']);
    }
}
