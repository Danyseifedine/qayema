<?php

namespace App\Filament\Admin\Resources\BlockedIps\Pages;

use App\Filament\Admin\Resources\BlockedIps\BlockedIpResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListBlockedIps extends ListRecords
{
    protected static string $resource = BlockedIpResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Block IP'),
        ];
    }
}
