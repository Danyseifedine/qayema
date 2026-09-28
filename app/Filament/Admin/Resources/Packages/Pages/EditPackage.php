<?php

namespace App\Filament\Admin\Resources\Packages\Pages;

use App\Enums\Feature;
use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Packages\PackageResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPackage extends EditRecord
{
    use KeepsTranslations;

    protected static string $resource = PackageResource::class;

    /**
     * Flags are stored as 0/1 so they can share the features map with limits,
     * but a Toggle wants a boolean. Every feature is filled, so one added to
     * the enum after this package was written opens on its default.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        $stored = (array) ($data['features'] ?? []);

        foreach (Feature::cases() as $feature) {
            // A key the package does not carry yet reads as the feature's
            // default, never as empty, which a limit field would save as
            // unlimited.
            $value = array_key_exists($feature->value, $stored) ? $stored[$feature->value] : $feature->defaultValue();

            $data['features'][$feature->value] = $feature->isLimit() ? $value : (bool) $value;
        }

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
