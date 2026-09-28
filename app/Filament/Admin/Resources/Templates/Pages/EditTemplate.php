<?php

namespace App\Filament\Admin\Resources\Templates\Pages;

use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Templates\TemplateResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTemplate extends EditRecord
{
    use KeepsTranslations;

    protected static string $resource = TemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillTranslations($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mergeTranslations($data);
    }
}
