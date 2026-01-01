<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Menu;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MenuStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalMenus = Menu::count();
        $activeMenus = Menu::where('is_active', true)->count();
        $inactiveMenus = Menu::where('is_active', false)->count();
        $totalDishes = \App\Models\Dish::count();
        $totalCategories = \App\Models\Category::count();

        return [
            Stat::make('Total Menus', $totalMenus)
                ->description('All menus in the system')
                ->descriptionIcon('heroicon-o-rectangle-stack')
                ->color('primary')
                ->chart([7, 12, 15, 18, $totalMenus]),

            Stat::make('Active Menus', $activeMenus)
                ->description($totalMenus > 0 ? round(($activeMenus / $totalMenus) * 100, 1).'% of total' : '0% of total')
                ->descriptionIcon('heroicon-o-check-circle')
                ->color('success'),

            Stat::make('Total Dishes', $totalDishes)
                ->description('Across all menus')
                ->descriptionIcon('heroicon-o-squares-2x2')
                ->color('info'),

            Stat::make('Total Categories', $totalCategories)
                ->description('Menu categories')
                ->descriptionIcon('heroicon-o-tag')
                ->color('warning'),
        ];
    }
}
