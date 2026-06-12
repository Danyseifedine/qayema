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
                    ->searchable(query: fn ($query, string $search) => $query
                        ->where('name->ar', 'like', "%{$search}%")
                        ->orWhere('name->en', 'like', "%{$search}%"))
                    ->weight('bold'),

                TextColumn::make('user.name')
                    ->label('Owner')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),

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
                    ->getStateUsing(fn (Restaurant $record): int => $record->dish_limit)
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->placeholder('N/A')
                    ->toggleable(),

                TextColumn::make('total_views')
                    ->label('Total Views')
                    ->getStateUsing(fn (Restaurant $record): int => $record->statistics()->count())
                    ->badge()
                    ->color('info')
                    ->sortable(false),

                TextColumn::make('unique_visitors')
                    ->label('Unique Visitors')
                    ->getStateUsing(fn (Restaurant $record): int => $record->statistics()->distinct('session_id')->count('session_id'))
                    ->badge()
                    ->color('success')
                    ->toggleable(),

                TextColumn::make('qr_scans')
                    ->label('QR Scans')
                    ->getStateUsing(fn (Restaurant $record): int => $record->statistics()->where('via_qr', true)->count())
                    ->badge()
                    ->color('warning')
                    ->toggleable(),

                TextColumn::make('whatsapp_orders')
                    ->label('WhatsApp Orders')
                    ->getStateUsing(fn (Restaurant $record): int => (int) $record->statistics()->sum('whatsapp_orders'))
                    ->badge()
                    ->color('primary')
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
