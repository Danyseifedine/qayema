<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Resources\ContactMessages\ContactMessageResource;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Models\ContactMessage;
use App\Models\Restaurant;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;

/**
 * The admin home's first row: how many restaurants there are and how many
 * menus guests can open, who pays and what that adds up to a month, new
 * sign-ups, and what is waiting in the inbox. Each figure opens the list
 * behind it.
 */
class Overview extends StatsOverviewWidget
{
    protected static ?int $sort = 0;

    protected int|array|null $columns = ['md' => 2, 'xl' => 4];

    protected function getStats(): array
    {
        // Every restaurant figure in one query: each scope a count of its own.
        $count = fn ($query) => $query->selectRaw('count(*)');
        $restaurants = Restaurant::query()
            ->toBase()
            ->selectRaw('count(*) as total')
            ->selectRaw('sum(case when created_at >= ? then 1 else 0 end) as new', [now()->subDays(7)])
            ->selectSub($count(Restaurant::query()->menuOnline()), 'online')
            ->selectSub($count(Restaurant::query()->paying()), 'paying')
            ->selectSub($count(Restaurant::query()->paying()->where('pays_with_whish', true)), 'whish')
            ->first();
        $online = (int) $restaurants->online;
        $paying = (int) $restaurants->paying;
        $whish = (int) $restaurants->whish;
        // The menu's Inbox badge counts the same: read once for both.
        $open = ContactMessageResource::openCount();
        $requests = $open === 0 ? 0 : ContactMessage::query()->open()->whereNotNull('package_id')->count();

        return [
            Stat::make('Restaurants', Number::format((int) $restaurants->total))
                ->description(Number::format($online).' menus online · '.Number::format((int) $restaurants->new).' new this week')
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->url(RestaurantResource::getUrl()),
            Stat::make('Paying', Number::format($paying))
                ->description(Number::format($whish).' through Whish')
                ->icon(Heroicon::OutlinedBanknotes)
                ->url(RestaurantResource::getUrl(parameters: ['activeTab' => 'paying'])),
            Stat::make('A month', $this->monthlyIncome())
                ->description('What the paying restaurants pay')
                ->icon(Heroicon::OutlinedCurrencyDollar)
                ->color('success'),
            Stat::make('Inbox', Number::format($open))
                ->description($open === 0 ? 'Nothing waiting' : Number::format($requests).' package '.str('request')->plural($requests))
                ->icon(Heroicon::OutlinedInbox)
                ->color($open === 0 ? 'gray' : 'warning')
                ->url(ContactMessageResource::getUrl()),
        ];
    }

    /** "$1,240.00", or each currency's total when there are several (Restaurant::monthlyIncome()). */
    private function monthlyIncome(): string
    {
        $totals = Restaurant::monthlyIncome();

        if ($totals->isEmpty()) {
            return Number::currency(0, 'USD');
        }

        return $totals
            ->map(fn (array $row): string => (string) Number::currency($row['cents'] / 100, $row['currency']))
            ->implode(' + ');
    }
}
