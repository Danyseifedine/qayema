<?php

namespace App\Filament\Admin\Resources\RestaurantSocialLinks\Pages;

use App\Filament\Admin\Resources\RestaurantSocialLinks\RestaurantSocialLinkResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditRestaurantSocialLink extends EditRecord
{
    protected static string $resource = RestaurantSocialLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
