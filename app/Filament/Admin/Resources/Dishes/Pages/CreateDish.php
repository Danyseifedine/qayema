<?php

namespace App\Filament\Admin\Resources\Dishes\Pages;

use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Dishes\DishResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDish extends CreateRecord
{
    use KeepsTranslations;

    protected static string $resource = DishResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mergeTranslations($data);
    }
}
