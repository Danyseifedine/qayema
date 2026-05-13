<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Category;
use App\Models\ContactMessage;
use App\Models\Dish;
use App\Models\Restaurant;
use App\Models\RestaurantStatistic;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class TodayOverviewWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected function getStats(): array
    {
        $today = today();

        return [
            Stat::make('New Users Today', User::whereDate('created_at', $today)->count())
                ->description('Total: '.number_format(User::count()))
                ->descriptionIcon('heroicon-o-user-plus')
                ->color('success'),

            Stat::make('New Restaurants Today', Restaurant::whereDate('created_at', $today)->count())
                ->description('Total: '.number_format(Restaurant::count()).' · Active: '.Restaurant::where('is_active', true)->count())
                ->descriptionIcon('heroicon-o-building-storefront')
                ->color('primary'),

            Stat::make('Dishes Added Today', Dish::whereDate('created_at', $today)->count())
                ->description('Total dishes: '.number_format(Dish::count()))
                ->descriptionIcon('heroicon-o-squares-2x2')
                ->color('warning'),

            Stat::make('Categories Added Today', Category::whereDate('created_at', $today)->count())
                ->description('Total categories: '.number_format(Category::count()))
                ->descriptionIcon('heroicon-o-tag')
                ->color('info'),

            Stat::make('Page Views Today', number_format(RestaurantStatistic::whereDate('viewed_at', $today)->count()))
                ->description('Unique visitors: '.RestaurantStatistic::whereDate('viewed_at', $today)->distinct('session_id')->count('session_id'))
                ->descriptionIcon('heroicon-o-eye')
                ->color('success'),

            Stat::make('QR Scans Today', number_format(RestaurantStatistic::whereDate('viewed_at', $today)->where('via_qr', true)->count()))
                ->description('All-time QR scans: '.number_format(RestaurantStatistic::where('via_qr', true)->count()))
                ->descriptionIcon('heroicon-o-qr-code')
                ->color('primary'),

            Stat::make('WhatsApp Orders Today', number_format(RestaurantStatistic::whereDate('viewed_at', $today)->sum('whatsapp_orders')))
                ->description('All-time orders: '.number_format(RestaurantStatistic::sum('whatsapp_orders')))
                ->descriptionIcon('heroicon-o-chat-bubble-left-ellipsis')
                ->color('success'),

            Stat::make('Contact Messages Today', ContactMessage::whereDate('created_at', $today)->count())
                ->description('Total messages: '.number_format(ContactMessage::count()))
                ->descriptionIcon('heroicon-o-envelope')
                ->color('warning'),
        ];
    }
}
