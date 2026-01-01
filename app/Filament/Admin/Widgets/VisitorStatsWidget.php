<?php

namespace App\Filament\Admin\Widgets;

use App\Models\MenuStatistic;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class VisitorStatsWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $totalViews = MenuStatistic::sum('page_views') ?? 0;
        $uniqueVisitors = MenuStatistic::distinct('session_id')->count('session_id');
        $todayViews = MenuStatistic::whereDate('viewed_at', today())->sum('page_views') ?? 0;
        $avgTimeSpent = MenuStatistic::whereNotNull('time_spent')
            ->where('time_spent', '>', 0)
            ->avg('time_spent') ?? 0;

        return [
            Stat::make('Total Page Views', number_format($totalViews))
                ->description('All time page views')
                ->descriptionIcon('heroicon-o-eye')
                ->color('primary')
                ->chart([100, 200, 300, 400, $totalViews]),

            Stat::make('Unique Visitors', number_format($uniqueVisitors))
                ->description('Distinct sessions')
                ->descriptionIcon('heroicon-o-users')
                ->color('success'),

            Stat::make('Today\'s Views', number_format($todayViews))
                ->description('Page views today')
                ->descriptionIcon('heroicon-o-calendar')
                ->color('info'),

            Stat::make('Avg. Time Spent', $this->formatTime($avgTimeSpent))
                ->description('Average session duration')
                ->descriptionIcon('heroicon-o-clock')
                ->color('warning'),
        ];
    }

    protected function formatTime(int $seconds): string
    {
        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return $remainingSeconds > 0 ? "{$minutes}m {$remainingSeconds}s" : "{$minutes}m";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return $remainingMinutes > 0 ? "{$hours}h {$remainingMinutes}m" : "{$hours}h";
    }
}
