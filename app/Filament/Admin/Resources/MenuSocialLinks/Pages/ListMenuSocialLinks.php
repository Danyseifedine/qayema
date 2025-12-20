<?php

namespace App\Filament\Admin\Resources\MenuSocialLinks\Pages;

use App\Filament\Admin\Resources\MenuSocialLinks\MenuSocialLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMenuSocialLinks extends ListRecords
{
    protected static string $resource = MenuSocialLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
