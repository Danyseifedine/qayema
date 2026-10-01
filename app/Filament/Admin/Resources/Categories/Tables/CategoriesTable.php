<?php

namespace App\Filament\Admin\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->placeholder('N/A')
                    ->counts('dishes')
                    ->sortable(),
                TextColumn::make('display_order')
                    ->label('Order')
                    ->placeholder('N/A')
                    ->sortable(),
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
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                // Its dishes stay on the menu, under "No category".
                DeleteAction::make()
                    ->modalDescription('The category is deleted. Its dishes stay on the menu, without a category.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order');
    }
}
