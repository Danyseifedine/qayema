<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Filament\Admin\Resources\RestaurantSocialLinks\RestaurantSocialLinkResource;
use Filament\Resources\RelationManagers\RelationManager;

/** This restaurant's social links, in the Social links list's own table. */
class SocialLinksRelationManager extends RelationManager
{
    protected static string $relationship = 'socialLinks';

    protected static ?string $relatedResource = RestaurantSocialLinkResource::class;

    protected static ?string $title = 'Social links';

    public function isReadOnly(): bool
    {
        return false;
    }
}
