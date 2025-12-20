<?php

namespace App\Filament\Admin\Resources\MenuSettings\Pages;

use App\Filament\Admin\Resources\MenuSettings\MenuSettingResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenuSettings extends ListRecords
{
    protected static string $resource = MenuSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
