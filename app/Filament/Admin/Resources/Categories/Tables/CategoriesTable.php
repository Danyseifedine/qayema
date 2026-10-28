<?php

namespace App\Filament\Admin\Resources\Categories\Tables;

use App\Filament\Admin\Resources\Categories\CategoryResource;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CategoriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable()
                    // The restaurant's own page lists only its rows.
                    ->hidden(fn (mixed $livewire): bool => $livewire instanceof RelationManager),
                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->placeholder('-')
                    ->counts('dishes')
                    ->sortable(),
                TextColumn::make('display_order')
                    ->label('Order')
                    ->placeholder('-')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->placeholder('-')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload()
                    // The restaurant's own page lists only its rows.
                    ->hidden(fn (mixed $livewire): bool => $livewire instanceof RelationManager),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription(CategoryResource::DELETE_WARNING),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->modalDescription(CategoryResource::DELETE_WARNING),
                ]),
            ])
            ->defaultSort('display_order');
    }
}
