<?php

namespace App\Filament\Admin\Resources\Packages\Pages;

use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Packages\PackageResource;
use App\Filament\Admin\Resources\Packages\Schemas\PackageForm;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPackage extends EditRecord
{
    use KeepsTranslations;

    protected static string $resource = PackageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (): bool => PackageResource::canDelete($this->record))
                ->modalDescription(fn (): string => PackageResource::deleteWarning($this->record)),
        ];
    }

    /**
     * Every feature filled (PackageForm::formFeatures()), so one added to the
     * enum after this package was written opens on its default.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['features'] = PackageForm::formFeatures((array) ($data['features'] ?? []));

        return $this->fillTranslations($data);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        return $this->mergeTranslations($data);
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Package updated')
            ->body('Every restaurant on this package picks this up immediately.');
    }
}
