<?php

namespace App\Filament\Admin\Resources\MenuSocialLinks\Pages;

use App\Filament\Admin\Resources\MenuSocialLinks\MenuSocialLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditMenuSocialLink extends EditRecord
{
    protected static string $resource = MenuSocialLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
