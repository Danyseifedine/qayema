<?php

namespace App\Filament\Admin\Resources\BlockedIps\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BlockedIpsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('ip')
                    ->label('IP Address')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->weight('bold'),
                TextColumn::make('reason')
                    ->placeholder('N/A')
                    ->searchable()
                    ->wrap()
                    ->toggleable(),
                TextColumn::make('expires_at')
                    ->label('Expires')
                    ->placeholder('Permanent')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Blocked At')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TernaryFilter::make('active')
                    ->label('Status')
                    ->placeholder('All blocks')
                    ->trueLabel('Active')
                    ->falseLabel('Expired')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->active(),
                        false: fn (Builder $query): Builder => $query->whereNotNull('expires_at')
                            ->where('expires_at', '<=', now()),
                        blank: fn (Builder $query): Builder => $query,
                    ),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->label('Unblock')
                    ->icon(Heroicon::OutlinedLockOpen)
                    ->modalHeading('Unblock IP')
                    ->modalDescription('This will remove the block and restore access for this IP address.'),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Unblock selected'),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
