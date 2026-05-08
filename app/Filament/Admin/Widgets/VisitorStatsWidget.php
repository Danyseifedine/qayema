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
        $totalViews = (int) RestaurantStatistic::sum('page_views');
        $uniqueVisitors = RestaurantStatistic::distinct('session_id')->count('session_id');
        $todayViews = (int) RestaurantStatistic::whereDate('viewed_at', today())->sum('page_views');
        $avgTimeSpent = (float) (RestaurantStatistic::whereNotNull('time_spent')
            ->where('time_spent', '>', 0)
            ->avg('time_spent') ?? 0);

        $last7Days = RestaurantStatistic::query()
            ->selectRaw('DATE(viewed_at) as date, SUM(page_views) as total')
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

            Stat::make('Avg. Time Spent', $this->formatTime((int) round($avgTimeSpent)))
                ->description('Per session with recorded exit')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }

    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = (int) floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = (int) floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    }
}
