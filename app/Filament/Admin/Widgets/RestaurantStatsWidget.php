<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Category;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class RestaurantStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $totalRestaurants = Restaurant::count();
        $activeRestaurants = Restaurant::where('is_active', true)->count();
        $totalUsers = User::count();
        $totalDishes = Dish::count();
        $totalCategories = Category::count();

        $restaurantTrend = Restaurant::query()
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('created_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count')
            ->toArray();

        return [
            Stat::make('Total Restaurants', number_format($totalRestaurants))
                ->description($activeRestaurants.' active · '.($totalRestaurants - $activeRestaurants).' inactive')
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary')
                ->chart(count($restaurantTrend) > 1 ? $restaurantTrend : [0, $totalRestaurants]),

            Stat::make('Menu Owners', number_format($totalUsers))
                ->description('Registered user accounts')
                ->descriptionIcon('heroicon-o-users')
                ->color('info'),

            Stat::make('Total Dishes', number_format($totalDishes))
                ->description('Across all restaurants')
                ->descriptionIcon('heroicon-o-squares-2x2')
                ->color('success'),

            Stat::make('Total Categories', number_format($totalCategories))
                ->description('Menu section groups')
                ->descriptionIcon('heroicon-o-tag')
                ->color('warning'),
        ];
    }
}
