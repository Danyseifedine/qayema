<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Ai\WritingHelpLog;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * The dashboard's AI buttons over the last 30 days, every restaurant
 * together (WritingHelpLog): how much they are used and what the provider
 * is asked for, so a jump shows here before it shows on the bill.
 */
class WritingHelpUsage extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'AI writing help, last 30 days';

    protected function getStats(): array
    {
        $usage = app(WritingHelpLog::class)->overall();

        return [
            Stat::make('Uses', Number::format($usage['uses']))
                ->description('Texts the owners got'),
            Stat::make('Tokens', Number::format($usage['tokens']))
                ->description('Sent and received, retries included'),
            Stat::make('Restaurants', Number::format($usage['restaurants']))
                ->description('That used it at least once'),
        ];
    }
}
