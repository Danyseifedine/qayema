<?php

namespace App\Filament\Admin\Resources\Templates\Pages;

use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Templates\TemplateResource;
use Filament\Resources\Pages\CreateRecord;

class CreateTemplate extends CreateRecord
{
    use KeepsTranslations;

    protected static string $resource = TemplateResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        return $this->mergeTranslations($data);
    }
}
