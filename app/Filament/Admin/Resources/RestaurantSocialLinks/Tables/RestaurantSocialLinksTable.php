<?php

namespace App\Filament\Admin\Resources\RestaurantSocialLinks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class RestaurantSocialLinksTable
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
                TextColumn::make('platform')
                    ->placeholder('N/A')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'instagram' => 'pink',
                        'x' => 'sky',
                        'facebook' => 'blue',
                        'tiktok' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'instagram' => 'Instagram',
                        'x' => 'X (Twitter)',
                        'facebook' => 'Facebook',
                        'tiktok' => 'TikTok',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->placeholder('N/A')
                    ->limit(50)
                    ->copyable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->options([
                        'instagram' => 'Instagram',
                        'x' => 'X (Twitter)',
                        'facebook' => 'Facebook',
                        'tiktok' => 'TikTok',
                    ]),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at');
    }
}
