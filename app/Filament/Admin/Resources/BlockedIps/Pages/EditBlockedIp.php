<?php

namespace App\Filament\Admin\Resources\BlockedIps\Pages;

use App\Filament\Admin\Resources\BlockedIps\BlockedIpResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditBlockedIp extends EditRecord
{
    protected static string $resource = BlockedIpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()->label('Unblock'),
        ];
    }
}
