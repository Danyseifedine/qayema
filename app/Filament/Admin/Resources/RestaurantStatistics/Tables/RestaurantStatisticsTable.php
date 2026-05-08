<?php

namespace App\Filament\Admin\Resources\RestaurantStatistics\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantStatisticsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('viewed_at')
                    ->label('Viewed At')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->viewed_at?->format('M d, Y H:i')),
                TextColumn::make('time_spent')
                    ->label('Time Spent')
                    ->placeholder('N/A')
                    ->formatStateUsing(function ($state) {
                        if (! $state) {
                            return 'N/A';
                        }
                        $minutes = floor($state / 60);
                        $seconds = $state % 60;

                        return $minutes > 0 ? "{$minutes}m {$seconds}s" : "{$seconds}s";
                    })
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => $state > 60 ? 'success' : ($state > 30 ? 'warning' : 'gray')),
                TextColumn::make('page_views')
                    ->label('Page Views')
                    ->placeholder('N/A')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('info'),
                TextColumn::make('device_type')
                    ->label('Device')
                    ->placeholder('N/A')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'Unknown'))
                    ->color(fn ($state) => match ($state) {
                        'mobile' => 'primary',
                        'desktop' => 'success',
                        'tablet' => 'warning',
                        default => 'gray',
                    })
                    ->toggleable(),
                TextColumn::make('browser')->placeholder('N/A')->toggleable(),
                TextColumn::make('os')->label('OS')->placeholder('N/A')->toggleable(),
                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('device_type')
                    ->label('Device Type')
                    ->options(['mobile' => 'Mobile', 'desktop' => 'Desktop', 'tablet' => 'Tablet']),
                Filter::make('viewed_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('viewed_from')->label('Viewed From'),
                        \Filament\Forms\Components\DatePicker::make('viewed_until')->label('Viewed Until'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['viewed_from'], fn ($q, $d) => $q->whereDate('viewed_at', '>=', $d))
                        ->when($data['viewed_until'], fn ($q, $d) => $q->whereDate('viewed_at', '<=', $d))
                    ),
                Filter::make('has_time_spent')
                    ->label('Has Time Spent')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('time_spent')),
            ])
            ->recordActions([ViewAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('viewed_at', 'desc')
            ->poll('30s');
    }
}
