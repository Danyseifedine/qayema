<?php

namespace App\Filament\Admin\Actions;

use App\Models\Package;
use App\Models\Restaurant;
use App\Services\Packages\PackageAssigner;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;

/** Back on the default package, forever. Nothing the owner made is deleted. */
class ResetPackageAction
{
    public static function make(): Action
    {
        return Action::make('resetPackage')
            ->label(fn (): string => 'Back to '.Package::default()?->name)
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('danger')
            ->requiresConfirmation()
            ->modalDescription('The restaurant keeps everything it made; what the default package does not include is hidden until it moves up again.')
            ->schema([
                TextInput::make('note')->label('Note')->maxLength(255)->helperText('Kept in the package history.'),
            ])
            ->visible(fn (Restaurant $record): bool => $record->package_id !== Package::default()?->id || $record->package_ends_at !== null)
            ->action(function (Restaurant $record, array $data): void {
                app(PackageAssigner::class)->reset($record, $data['note'] ?? null);

                Notification::make()->title('Back on the default package')->success()->send();
            });
    }
}
