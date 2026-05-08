<?php

namespace App\Filament\Admin\Resources\RestaurantSocialLinks\Pages;

use App\Filament\Admin\Resources\RestaurantSocialLinks\RestaurantSocialLinkResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRestaurantSocialLinks extends ListRecords
{
    protected static string $resource = RestaurantSocialLinkResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
