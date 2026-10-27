<?php

namespace App\Filament\Admin\Actions;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Collection;

/**
 * Mark an inbox message dealt with, or open it again: one button that says
 * what it will do, on the list and on the message, and the same in bulk.
 * Applying a requested package marks the request handled by itself.
 */
class HandleMessageAction
{
    public static function make(): Action
    {
        return Action::make('handle')
            ->label(fn (ContactMessage $record): string => $record->isHandled() ? 'Reopen' : 'Mark handled')
            ->icon(fn (ContactMessage $record): Heroicon => $record->isHandled() ? Heroicon::OutlinedArrowUturnLeft : Heroicon::OutlinedCheck)
            ->color(fn (ContactMessage $record): string => $record->isHandled() ? 'gray' : 'success')
            ->action(function (ContactMessage $record): void {
                $record->isHandled() ? $record->reopen() : $record->markHandled();

                Notification::make()
                    ->title($record->isHandled() ? 'Marked handled' : 'Back in the open messages')
                    ->success()
                    ->send();
            });
    }

    public static function bulk(): BulkAction
    {
        return BulkAction::make('handleSelected')
            ->label('Mark handled')
            ->icon(Heroicon::OutlinedCheck)
            ->action(function (Collection $records): void {
                // One write; the ones already handled keep their first date.
                ContactMessage::query()->whereKey($records->modelKeys())->open()->update(['handled_at' => now()]);

                Notification::make()->title('Marked handled')->success()->send();
            })
            ->deselectRecordsAfterCompletion();
    }
}
