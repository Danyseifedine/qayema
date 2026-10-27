<?php

namespace App\Filament\Admin\Resources\Restaurants\RelationManagers;

use App\Filament\Admin\Resources\Categories\CategoryResource;
use Filament\Resources\RelationManagers\RelationManager;

/** This restaurant's categories, in the Categories list's own table. */
class CategoriesRelationManager extends RelationManager
{
    protected static string $relationship = 'categories';

    protected static ?string $relatedResource = CategoryResource::class;

    protected static ?string $title = 'Categories';

    public function isReadOnly(): bool
    {
        return false;
    }
}
