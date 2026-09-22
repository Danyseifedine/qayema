<?php

namespace App\Filament\Admin\Widgets;

use App\Models\RestaurantStatistic;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalViews = RestaurantStatistic::count();
        $uniqueVisitors = RestaurantStatistic::distinct('session_id')->count('session_id');
        $todayViews = RestaurantStatistic::whereDate('viewed_at', today())->count();
        $qrScans = RestaurantStatistic::where('via_qr', true)->count();

        $last7Days = RestaurantStatistic::query()
            ->selectRaw('DATE(viewed_at) as date, COUNT(*) as total')
            ->where('viewed_at', '>=', now()->subDays(6))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('total')
            ->map(fn ($v) => (int) $v)
            ->toArray();

        $weekTrend = count($last7Days) > 1 ? $last7Days : [0, $todayViews];

        return [
            Stat::make('Total Page Views', number_format($totalViews))
                ->description('All time across all menus')
                ->descriptionIcon('heroicon-o-eye')
                ->color('primary')
                ->chart($weekTrend),

            Stat::make('Unique Sessions', number_format($uniqueVisitors))
                ->description('Distinct visitor sessions')
                ->descriptionIcon('heroicon-o-cursor-arrow-rays')
                ->color('success'),

            Stat::make('Views Today', number_format($todayViews))
                ->description('Page loads today')
                ->descriptionIcon('heroicon-o-calendar-days')
                ->color('info'),

            Stat::make('QR Scans', number_format($qrScans))
                ->description('Visits that came from a QR code')
                ->descriptionIcon('heroicon-o-qr-code')
                ->color('warning'),
        ];
    }
}
