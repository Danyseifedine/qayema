<?php

namespace App\Filament\Admin\Resources\Menus\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenusTable
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
                TextColumn::make('user.name')
                    ->label('Owner')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('menu_style')
                    ->placeholder('N/A')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'restaurant' => 'success',
                        'home' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'restaurant' => 'Restaurant',
                        'home' => 'Home Cook',
                        default => $state,
                    })
                    ->sortable(),
                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->placeholder('N/A')
                    ->counts('dishes')
                    ->sortable(),
                TextColumn::make('dish_limit')
                    ->label('Limit')
                    ->placeholder('N/A')
                    ->sortable(),
                TextColumn::make('categories_count')
                    ->label('Categories')
                    ->placeholder('N/A')
                    ->counts('categories')
                    ->toggleable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('menu_style')
                    ->label('Menu Style')
                    ->options([
                        'restaurant' => 'Restaurant',
                        'home' => 'Home Cook',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                SelectFilter::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload(),
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
