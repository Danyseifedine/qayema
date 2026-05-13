<?php

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserCascadeDeletionService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->placeholder('N/A')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('email')
                    ->label('Email')
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

                IconColumn::make('onboarding_completed_at')
                    ->label('Setup')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-clock')
                    ->trueColor('success')
                    ->falseColor('warning')
                    ->tooltip(fn (User $record): string => $record->onboarding_completed_at
                        ? 'Completed '.$record->onboarding_completed_at->diffForHumans()
                        : 'Step '.$record->onboarding_step.' of 6')
                    ->getStateUsing(fn (User $record): bool => ! is_null($record->onboarding_completed_at)),

                TextColumn::make('restaurant.name')
                    ->label('Restaurant')
                    ->placeholder('—')
                    ->searchable()
                    ->sortable()
                    ->url(fn (User $record): ?string => $record->restaurant
                        ? route('filament.admin.resources.restaurants.edit', ['record' => $record->restaurant->getKey()])
                        : null),

                TextColumn::make('dishes_count')
                    ->label('Dishes')
                    ->getStateUsing(fn (User $record): string => (string) ($record->restaurant?->dishes()->count() ?? '—'))
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('views_count')
                    ->label('Total Views')
                    ->getStateUsing(fn (User $record): string => (string) ($record->restaurant?->statistics()->count() ?? '—'))
                    ->placeholder('—')
                    ->badge()
                    ->color('info')
                    ->toggleable(),

                TextColumn::make('restaurant.is_active')
                    ->label('Menu Active')
                    ->placeholder('—')
                    ->badge()
                    ->color(fn ($state): string => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state): string => $state ? 'Active' : 'Inactive')
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        UserRole::Admin->value => 'Admin',
                        UserRole::MenuOwner->value => 'Menu Owner',
                    ]),

                Filter::make('onboarding_completed')
                    ->label('Setup complete')
                    ->query(fn (Builder $query) => $query->whereNotNull('onboarding_completed_at')),

                Filter::make('onboarding_pending')
                    ->label('Setup pending')
                    ->query(fn (Builder $query) => $query->whereNull('onboarding_completed_at')),

                Filter::make('joined_today')
                    ->label('Joined today')
                    ->query(fn (Builder $query) => $query->whereDate('created_at', today())),

                Filter::make('joined_this_week')
                    ->label('Joined this week')
                    ->query(fn (Builder $query) => $query->where('created_at', '>=', now()->startOfWeek())),
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
                DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalHeading('Delete user')
                    ->modalDescription('This permanently deletes the user, their restaurant, all categories and dishes (including images), social links, statistics, profile media, and their sessions.')
                    ->action(function (User $record): void {
                        app(UserCascadeDeletionService::class)->delete($record);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->requiresConfirmation()
                        ->modalHeading('Delete selected users')
                        ->modalDescription('Each selected user will be fully removed along with their restaurant, all dishes, categories, images, and related data.')
                        ->action(function ($records): void {
                            $deletion = app(UserCascadeDeletionService::class);
                            foreach ($records as $record) {
                                if (! $record instanceof User || ! auth()->user()?->can('delete', $record)) {
                                    continue;
                                }
                                $deletion->delete($record);
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
