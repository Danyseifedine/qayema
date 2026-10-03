<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Models\PackageChange;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Every package this restaurant has been on: what it moved from and to, the
 * dates it was given, who did it and why. Written only by the restaurant's
 * own save hook; an admin can delete a line but never add or edit one.
 */
class PackageChangesRelationManager extends RelationManager
{
    protected static string $relationship = 'packageChanges';

    protected static ?string $title = 'Package history';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        return $table
            // Each row names both packages: read with the page, not per row.
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['fromPackage', 'toPackage']))
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
            ->recordActions([
                DeleteAction::make()
                    ->modalDescription('This line is removed from the history. The restaurant stays on its current package.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
