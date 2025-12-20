<?php

namespace App\Filament\Admin\Resources\Dishes\Pages;

use App\Filament\Admin\Resources\Dishes\DishResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDish extends CreateRecord
{
    protected static string $resource = DishResource::class;
}
