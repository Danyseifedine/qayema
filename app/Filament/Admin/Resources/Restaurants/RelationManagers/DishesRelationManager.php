<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Filament\Admin\Resources\Dishes\DishResource;
use Filament\Resources\RelationManagers\RelationManager;

/**
 * This restaurant's dishes, in the Dishes list's own table (columns,
 * filters, availability switch); Edit opens the dish's page.
 */
class DishesRelationManager extends RelationManager
{
    protected static string $relationship = 'dishes';

    protected static ?string $relatedResource = DishResource::class;

    protected static ?string $title = 'Dishes';

    public function isReadOnly(): bool
    {
        return false;
    }
}
