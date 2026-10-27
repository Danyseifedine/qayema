<?php

namespace App\Filament\Admin\Resources\Offers\Tables;

use App\Enums\OfferDiscount;
use App\Enums\OfferPlacement;
use App\Enums\OfferRepeat;
use App\Models\Offer;
use Carbon\CarbonImmutable;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;

class OffersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            // Its restaurant names the row and gives the timezone the status
            // is judged in; read with the page, not per row.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['restaurant', 'media'])->withCount('dishes'))
            ->columns([
                TextColumn::make('title')
                    ->placeholder('Picture only')
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->where('title', 'like', "%{$search}%"))
                    ->weight('bold')
                    ->description(fn (Offer $record): string => self::schedule($record))
                    ->wrap(),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->searchable()
                    ->hidden(fn (mixed $livewire): bool => $livewire instanceof RelationManager),
                TextColumn::make('placements')
                    ->label('Shows as')
                    ->state(fn (Offer $record): array => array_map(fn (OfferPlacement $placement): string => match ($placement) {
                        OfferPlacement::Banner => 'Banner',
                        OfferPlacement::Popup => 'Pop-up',
                        OfferPlacement::Ad => 'Ad',
                    }, $record->placementList()))
                    ->badge()
                    ->color('gray'),
                TextColumn::make('discount')
                    ->state(fn (Offer $record): ?string => match (true) {
                        ! $record->hasDiscount() => null,
                        $record->discount_type === OfferDiscount::Percent => '-'.Number::format((float) $record->discount_value).'% on '.$record->dishes_count.' '.str('dish')->plural((int) $record->dishes_count),
                        default => '-'.Number::currency((float) $record->discount_value, $record->restaurant->currency ?? 'USD').' on '.$record->dishes_count.' '.str('dish')->plural((int) $record->dishes_count),
                    })
                    ->placeholder('-'),
                TextColumn::make('status')
                    ->state(fn (Offer $record): string => $record->statusAt(CarbonImmutable::now(), $record->restaurant->localTimezone()))
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'live' => 'success',
                        'scheduled' => 'info',
                        'paused' => 'warning',
                        default => 'gray',
                    }),
                ToggleColumn::make('is_active')
                    ->label('On'),
                TextColumn::make('created_at')
                    ->since()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload()
                    ->hidden(fn (mixed $livewire): bool => $livewire instanceof RelationManager),
            ])
            ->recordActions([
                DeleteAction::make()
                    ->modalDescription('The offer and its picture are deleted for good, with the dish it made (its deal), and it leaves the menu. Orders placed with it keep their price.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No offers yet')
            ->emptyStateDescription('Owners on Premium add offers from their dashboard.');
    }

    /** "Fri, Sat · 18:00 to 22:00 · from 1 Nov 2026 until 1 Dec 2026", in the restaurant's time. */
    public static function schedule(Offer $offer): string
    {
        $timezone = $offer->restaurant->localTimezone();
        $days = match ($offer->repeat) {
            OfferRepeat::Always => 'Every day',
            OfferRepeat::Weekly => implode(', ', array_map('ucfirst', (array) $offer->weekdays)),
            OfferRepeat::Monthly => 'Days '.implode(', ', (array) $offer->month_days).' of the month',
        };
        $times = $offer->time_windows === null
            ? 'all day'
            : implode(', ', array_map(fn (array $window): string => $window['from'].' to '.$window['to'], $offer->time_windows));
        $dates = array_filter([
            $offer->starts_at === null ? null : 'from '.$offer->starts_at->setTimezone($timezone)->toFormattedDateString(),
            $offer->ends_at === null ? null : 'until '.$offer->ends_at->setTimezone($timezone)->toFormattedDateString(),
        ]);

        return implode(' · ', array_filter([$days, $times, implode(' ', $dates)]));
    }
}
