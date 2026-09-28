<?php

namespace App\Filament\Admin\Actions;

use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/**
 * More time on the package a restaurant is already on: months added to its
 * end (or to today once it has ended), or no end at all.
 */
class ExtendPackageAction
{
    private const FOREVER = 'forever';

    public static function make(): Action
    {
        return Action::make('extendPackage')
            ->label('Extend')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->modalHeading(fn (Restaurant $record): string => 'Extend '.$record->package?->name.' for '.$record->name)
            ->modalDescription(fn (Restaurant $record): string => $record->package_ends_at === null
                ? 'It runs forever today.'
                : ($record->packageExpired() ? 'It ended on ' : 'It ends on ').$record->package_ends_at->toFormattedDayDateString().'.')
            ->modalSubmitActionLabel('Extend')
            // A package that runs forever has nothing to extend.
            ->visible(fn (Restaurant $record): bool => $record->package_ends_at !== null)
            ->schema([
                ToggleButtons::make('extend_by')
                    ->label('Add')
                    ->options(PackageFields::monthOptions() + [self::FOREVER => 'Forever'])
                    ->default(1)
                    ->inline()
                    ->required(),
                TextInput::make('note')
                    ->label('Note')
                    ->maxLength(255)
                    ->helperText('Kept in the package history.'),
            ])
            ->action(function (Restaurant $record, array $data): void {
                $months = $data['extend_by'] === self::FOREVER ? null : (int) $data['extend_by'];
                app(PackageAssigner::class)->extend($record, $months, $data['note'] ?? null);

                Notification::make()->title('Package extended')->success()->send();
            });
    }
}
