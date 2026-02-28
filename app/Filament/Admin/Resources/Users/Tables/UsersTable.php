<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                TextColumn::make('role')
                    ->placeholder('N/A')
                    ->badge()
                    ->color(fn (UserRole|string $state): string => match ($state instanceof UserRole ? $state : null) {
                        UserRole::Admin => 'danger',
                        UserRole::MenuOwner => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (UserRole|string $state): string => match ($state instanceof UserRole ? $state : null) {
                        UserRole::Admin => 'Admin',
                        UserRole::MenuOwner => 'Menu Owner',
                        default => is_string($state) ? $state : $state->value,
                    })
                    ->sortable(),
                TextColumn::make('restaurant_name')
                    ->label('Business Name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('phone')
                    ->placeholder('N/A')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('menus_count')
                    ->label('Menus')
                    ->placeholder('N/A')
                    ->counts('menus')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->placeholder('N/A')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        UserRole::Admin->value => 'Admin',
                        UserRole::MenuOwner->value => 'Menu Owner',
                    ]),
            ])
            ->recordActions([
                Action::make('impersonate')
                    ->label('Impersonate')
                    ->icon(Heroicon::OutlinedUserCircle)
                    ->url(fn (User $record): string => route('impersonate', $record->getKey()))
                    ->openUrlInNewTab(false)
                    ->visible(fn (User $record): bool => auth()->id() !== $record->getKey() && $record->canBeImpersonated()),
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
