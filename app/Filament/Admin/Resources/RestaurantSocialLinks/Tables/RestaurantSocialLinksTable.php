<?php

namespace App\Filament\Admin\Resources\RestaurantSocialLinks\Tables;

use App\Filament\Admin\Resources\RestaurantSocialLinks\Schemas\RestaurantSocialLinkForm;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
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
        $platforms = RestaurantSocialLinkForm::platformOptions();

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
                        'youtube', 'pinterest' => 'danger',
                        'snapchat' => 'warning',
                        'telegram' => 'info',
                        'tripadvisor' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => $platforms[$state] ?? ucfirst($state))
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
                    ->options($platforms),
                SelectFilter::make('restaurant_id')
                    ->label('Restaurant')
                    ->relationship('restaurant', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([ViewAction::make(), EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])])
            ->defaultSort('created_at');
    }
}
