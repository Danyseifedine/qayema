<?php

namespace App\Filament\Admin\Resources\CoinPacks\Tables;

use App\Models\CoinPack;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CoinPacksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('coins')
                    ->numeric()
                    ->sortable()
                    ->badge(),
                IconColumn::make('is_active')
                    ->label('On sale')
                    ->boolean(),
                IconColumn::make('sellable')
                    ->label('Sellable now')
                    ->boolean()
                    ->getStateUsing(fn (CoinPack $record): bool => $record->isSellable())
                    ->tooltip('Active and has a Paddle price for the current environment.'),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->sortable()
                    ->toggleable(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}
