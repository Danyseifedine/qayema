<?php

namespace App\Filament\Admin\Resources\Dishes\Tables;

use App\Models\Dish;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DishesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['restaurant', 'offer'])->withCount(['variants', 'addons']))
            ->columns([
                SpatieMediaLibraryImageColumn::make('image')
                    ->collection('image')
                    ->label('Image')
                    ->circular()
                    ->limit(3)
                    ->placeholder('N/A')
                    ->toggleable(),
                TextColumn::make('name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    // The restaurant's own page lists only its rows.
                    ->hidden(fn (mixed $livewire): bool => $livewire instanceof RelationManager),
                TextColumn::make('category.name')
                    ->label('Category')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('price')
                    ->placeholder('N/A')
                    // In the dish's own currency: the second one, or its restaurant's main one.
                    ->money(fn (Dish $record): string => $record->currency ?? $record->restaurant->currency)
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('choices')
                    ->label('Variants / add-ons')
                    ->state(fn (Dish $record): string => $record->variants_count.' / '.$record->addons_count)
                    ->toggleable(),
                // An offer's deal dish: on the menu only while that offer is live.
                TextColumn::make('offer.title')
                    ->label('Made by offer')
                    ->badge()
                    ->color('warning')
                    ->toggleable(),
                ToggleColumn::make('is_available')
                    ->label('Available'),
                TextColumn::make('display_order')
                    ->label('Order')
                    ->placeholder('N/A')
                    ->sortable()
                    ->toggleable(),
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
                    ->preload()
                    // The restaurant's own page lists only its rows.
                    ->hidden(fn (mixed $livewire): bool => $livewire instanceof RelationManager),
                SelectFilter::make('category_id')
                    ->label('Category')
                    // On a restaurant's page, only that restaurant's categories.
                    ->relationship('category', 'name', fn (Builder $query, mixed $livewire): Builder => $livewire instanceof RelationManager
                        ? $query->where('restaurant_id', $livewire->getOwnerRecord()->getKey())
                        : $query)
                    ->searchable()
                    ->preload(),
                SelectFilter::make('is_available')
                    ->label('Status')
                    ->options([1 => 'Available', 0 => 'Unavailable']),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->modalDescription('The dish and its photo are deleted for good.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order');
    }
}
