<?php

namespace App\Filament\Admin\Resources\Restaurants\Pages;

use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRestaurant extends EditRecord
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('impersonate')
                ->label('Log in as owner')
                ->icon(Heroicon::OutlinedUserCircle)
                ->color('gray')
                ->url(fn (): string => route('impersonate', $this->record->user_id))
                ->visible(fn (): bool => $this->record->user?->canBeImpersonated() ?? false),
            DeleteAction::make(),
        ];
    }

    /** Open the package fields on what the restaurant has. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return [...$data, ...PackageFields::stateFor($this->record)];
    }

    /** Turn "for how long" into an end date, and hand the note to the history. */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['package_ends_at'] = PackageFields::endsAt($data);
        $this->record->packageChangeNote = $data['note'] ?? null;

        return collect($data)->except(PackageFields::EXTRA)->all();
    }
}
