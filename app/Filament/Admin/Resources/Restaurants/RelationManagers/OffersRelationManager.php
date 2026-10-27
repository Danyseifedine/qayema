<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Filament\Admin\Resources\Offers\OfferResource;
use Filament\Resources\RelationManagers\RelationManager;

/** This restaurant's offers, in the Offers list's own table. */
class OffersRelationManager extends RelationManager
{
    protected static string $relationship = 'offers';

    protected static ?string $relatedResource = OfferResource::class;

    protected static ?string $title = 'Offers';

    public function isReadOnly(): bool
    {
        return false;
    }
}
