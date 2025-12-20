<?php

namespace App\Filament\Admin\Resources\MenuSocialLinks\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenuSocialLinksTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('menu.name')
                    ->label('Menu')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('platform')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'facebook' => 'blue',
                        'instagram' => 'pink',
                        'twitter' => 'sky',
                        'youtube' => 'red',
                        'tiktok' => 'gray',
                        'linkedin' => 'blue',
                        'pinterest' => 'red',
                        'snapchat' => 'yellow',
                        'whatsapp' => 'green',
                        'telegram' => 'blue',
                        'website' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter / X',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'linkedin' => 'LinkedIn',
                        'pinterest' => 'Pinterest',
                        'snapchat' => 'Snapchat',
                        'whatsapp' => 'WhatsApp',
                        'telegram' => 'Telegram',
                        'website' => 'Website',
                        default => ucfirst($state),
                    })
                    ->searchable()
                    ->sortable(),
                TextColumn::make('url')
                    ->label('URL')
                    ->limit(50)
                    ->copyable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('display_order')
                    ->label('Order')
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('platform')
                    ->label('Platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'instagram' => 'Instagram',
                        'twitter' => 'Twitter / X',
                        'youtube' => 'YouTube',
                        'tiktok' => 'TikTok',
                        'linkedin' => 'LinkedIn',
                        'pinterest' => 'Pinterest',
                        'snapchat' => 'Snapchat',
                        'whatsapp' => 'WhatsApp',
                        'telegram' => 'Telegram',
                        'website' => 'Website',
                        'other' => 'Other',
                    ]),
                SelectFilter::make('is_active')
                    ->label('Status')
                    ->options([
                        1 => 'Active',
                        0 => 'Inactive',
                    ]),
                SelectFilter::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'name')
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
            ->defaultSort('display_order');
    }
}
