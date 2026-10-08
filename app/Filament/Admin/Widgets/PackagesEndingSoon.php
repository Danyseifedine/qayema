<?php

namespace App\Filament\Admin\Widgets;

use App\Filament\Admin\Actions\ChangePackageAction;
use App\Filament\Admin\Actions\ExtendPackageAction;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Models\Restaurant;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * The admin home: restaurants whose package ends in the next two weeks, and
 * those whose package ended in the last month, with Extend and Change package
 * at hand. Nothing is sold in the app, so this is where renewals happen.
 */
class PackagesEndingSoon extends TableWidget
{
    public const UPCOMING_DAYS = 14;

    public const RECENT_DAYS = 30;

    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Packages ending soon')
            ->description('Ending in the next '.self::UPCOMING_DAYS.' days, or ended in the last '.self::RECENT_DAYS.'.')
            ->query(fn (): Builder => Restaurant::query()
                ->with(['package', 'user'])
                ->where(fn (Builder $query) => $query
                    ->packageEndingWithin(self::UPCOMING_DAYS)
                    ->orWhereBetween('package_ends_at', [now()->subDays(self::RECENT_DAYS), now()])))
            ->defaultSort('package_ends_at')
            ->columns([
                TextColumn::make('name')
                    ->weight('bold')
                    ->url(fn (Restaurant $record): string => RestaurantResource::getUrl('edit', ['record' => $record])),
                TextColumn::make('user.email')
                    ->label('Owner')
                    // An owner who signed up with a username has no email.
                    ->state(fn (Restaurant $record): ?string => $record->user?->email ?? $record->user?->username)
                    ->copyable(),
                TextColumn::make('package.name')
                    ->label('Package')
                    ->badge()
                    ->color(fn (Restaurant $record): string => $record->packageExpired() ? 'danger' : 'warning')
                    ->description(fn (Restaurant $record): string => RestaurantsTable::packageDates($record)),
                TextColumn::make('package_ends_at')
                    ->label('Ends')
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                ExtendPackageAction::make(),
                ChangePackageAction::make(),
            ])
            ->emptyStateHeading('Nothing ending soon')
            ->emptyStateDescription('Every package runs past the next '.self::UPCOMING_DAYS.' days.')
            ->paginated([10, 25]);
    }
}
