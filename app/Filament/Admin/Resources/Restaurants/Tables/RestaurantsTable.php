<?php

namespace App\Filament\Admin\Resources\Restaurants\Tables;

use App\Models\Restaurant;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RestaurantsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('logo')
                    ->collection('logo')
                    ->label('Logo')
                    ->circular()
                    ->toggleable(),

                TextColumn::make('name')
                    ->placeholder('N/A')
                    // The whole JSON, so a name matches in whichever language
                    // the owner wrote it.
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('name', 'like', "%{$search}%"))
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('package.name')
                    ->label('Package')
                    ->placeholder('Default')
                    ->badge()
                    ->color(fn (Restaurant $record): string => $record->packageExpired() ? 'danger' : 'success')
                    ->description(fn (Restaurant $record): ?string => $record->packageExpired() ? 'Expired' : null)
                    ->toggleable(),

                TextColumn::make('template.name')
                    ->label('Template')
                    ->placeholder('None')
                    ->badge()
                    ->toggleable(),

                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->counts('dishes')
                    ->sortable(),

                TextColumn::make('dish_limit')
                    ->label('Dish Limit')
                    ->getStateUsing(fn (Restaurant $record): string => $record->dish_limit === null ? '∞' : (string) $record->dish_limit)
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->placeholder('N/A')
                    ->toggleable(),

                TextColumn::make('total_views')
                    ->label('Total Views')
                    ->getStateUsing(fn (Restaurant $record): int => $record->menuSessions()->count())
                    ->badge()
                    ->color('info')
                    ->sortable(false),

                TextColumn::make('unique_visitors')
                    ->label('Unique Visitors')
                    ->getStateUsing(fn (Restaurant $record): int => $record->menuSessions()->distinct('session_id')->count('session_id'))
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('qr_scans')
                    ->label('QR Scans')
                    ->getStateUsing(fn (Restaurant $record): int => $record->menuSessions()->where('via_qr', true)->count())
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Active'),

                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([1 => 'Active', 0 => 'Inactive']),

                SelectFilter::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('template_id')
                    ->label('Template')
                    ->relationship('template', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_today')
                    ->label('Created today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),

                Filter::make('created_this_week')
                    ->label('Created this week')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfWeek())),

                Filter::make('has_views')
                    ->label('Has visitor traffic')
                    ->query(fn (Builder $query) => $query->whereHas('statistics')),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
