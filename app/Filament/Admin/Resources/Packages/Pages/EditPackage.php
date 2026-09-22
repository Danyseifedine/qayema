<?php

namespace App\Filament\Admin\Resources\Packages\Pages;

use App\Enums\Feature;
use App\Enums\FeatureKind;
use App\Filament\Admin\Resources\Packages\PackageResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPackage extends EditRecord
{
    protected static string $resource = PackageResource::class;

    /**
     * Flags are stored as 0/1 so they can share the features map with limits,
     * but a Toggle wants a boolean.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (Feature::cases() as $feature) {
            if ($feature->kind() !== FeatureKind::Flag) {
                continue;
            }

            $data['features'][$feature->value] = (bool) ($data['features'][$feature->value] ?? $feature->defaultValue());
        }

        return $data;
    }

    protected function getSavedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Package updated')
            ->body('Every restaurant on this package picks this up immediately.');
    }
}
