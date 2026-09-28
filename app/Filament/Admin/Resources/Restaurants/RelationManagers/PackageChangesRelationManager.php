<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Models\PackageChange;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Every package this restaurant has been on: what it moved from and to, the
 * dates it was given, who did it and why. Read-only: it is a record.
 */
class PackageChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'packageChanges';

    protected static ?string $title = 'Package history';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('When')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('change')
                    ->label('Change')
                    ->state(fn (PackageChange $record): string => $record->from_package_id === null
                        ? 'Created on '.$record->toPackage?->name
                        : $record->fromPackage?->name.' → '.$record->toPackage?->name)
                    ->badge()
                    ->color('gray'),
                TextColumn::make('starts_at')
                    ->label('From')
                    ->date(),
                TextColumn::make('ends_at')
                    ->label('Until')
                    ->date()
                    ->placeholder('Forever'),
                TextColumn::make('changedBy.name')
                    ->label('By')
                    ->placeholder('System'),
                TextColumn::make('note')
                    ->placeholder('-')
                    ->wrap(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
