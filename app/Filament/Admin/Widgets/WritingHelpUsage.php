<?php

namespace App\Filament\Admin\Widgets;

use App\Services\Ai\WritingHelpCredits;
use App\Services\Ai\WritingHelpLog;
use App\Services\Ai\WritingHelpPrice;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * The dashboard's AI buttons over the last 30 days, every restaurant
 * together (WritingHelpLog): how much they are used, what the provider is
 * asked for and what it cost, so a jump shows here before it shows on the
 * bill, and what a credit really costs against what it is worth.
 */
class WritingHelpUsage extends StatsOverviewWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'AI writing help, last 30 days';

    protected function getStats(): array
    {
        $usage = app(WritingHelpLog::class)->overall();

        return [
            Stat::make('AI cost', WritingHelpPrice::format($usage['cost']))
                ->description(WritingHelpPrice::format($usage['cost_today']).' today')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('primary'),
            Stat::make('Uses', Number::format($usage['uses']))
                ->description('Texts the owners got'),
            Stat::make('AI credits used', Number::format($usage['credits']))
                ->description(self::perCredit($usage['cost'], $usage['credits'])),
            Stat::make('Tokens', Number::format($usage['tokens']))
                ->description('Sent and received, retries included'),
            Stat::make('Restaurants', Number::format($usage['restaurants']))
                ->description('That used it at least once'),
        ];
    }

    /** "Really $0.00008 a credit (worth $0.0001)": whether the credits' prices cover the cost. */
    private static function perCredit(float $cost, int $credits): string
    {
        $worth = 'worth '.WritingHelpPrice::format(WritingHelpCredits::dollarsOf(1));

        return $credits === 0
            ? "None yet (a credit is {$worth})"
            : 'Really '.WritingHelpPrice::format($cost / $credits)." a credit ({$worth})";
    }

    protected function getDescription(): ?string
    {
        return WritingHelpPrice::describe();
    }
}
