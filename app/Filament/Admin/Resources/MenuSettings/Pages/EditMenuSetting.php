<?php

namespace App\Filament\Admin\Resources\MenuSettings\Pages;

use App\Filament\Admin\Resources\MenuSettings\MenuSettingResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenuSetting extends EditRecord
{
    protected static string $resource = MenuSettingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
