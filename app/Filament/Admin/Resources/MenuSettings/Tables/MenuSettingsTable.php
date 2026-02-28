<?php

namespace App\Filament\Admin\Resources\MenuSettings\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MenuSettingsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('menu.name')
                    ->label('Menu')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('setting.title')
                    ->label('Setting')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('setting.key')
                    ->label('Key')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                TextColumn::make('setting.type')
                    ->label('Type')
                    ->placeholder('N/A')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'boolean' => 'success',
                        'integer' => 'info',
                        'float' => 'warning',
                        'json' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('value')
                    ->label('Value')
                    ->placeholder('N/A')
                    ->searchable()
                    ->limit(50)
                    ->formatStateUsing(function ($state, $record) {
                        $type = $record->setting?->type ?? 'string';

                        if ($type === 'boolean') {
                            return $state ? 'Yes' : 'No';
                        }

                        if ($type === 'json') {
                            return json_encode($state);
                        }

                        return $state ?? 'N/A';
                    }),
                TextColumn::make('setting.description')
                    ->label('Description')
                    ->placeholder('N/A')
                    ->limit(30)
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('setting.type')
                    ->label('Type')
                    ->relationship('setting', 'type')
                    ->options([
                        'string' => 'String',
                        'boolean' => 'Boolean',
                        'integer' => 'Integer',
                        'float' => 'Float',
                        'json' => 'JSON',
                    ]),
                SelectFilter::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('setting_id')
                    ->label('Setting')
                    ->relationship('setting', 'title')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
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
