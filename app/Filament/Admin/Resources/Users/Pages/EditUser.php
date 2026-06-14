<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Filament\Admin\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading('Delete user')
                ->modalDescription('This permanently deletes the user, every menu they own, all categories and dishes (including images), social links, statistics, settings, profile media, and their sessions. This cannot be undone.')
                ->action(function (): void {
                    $record = $this->record;
                    assert($record instanceof User);
                    $record->forceDelete();
                    Notification::make()
                        ->title('User deleted')
                        ->success()
                        ->send();
                    $this->redirect(UserResource::getUrl('index'));
                }),
        ];
    }
}
