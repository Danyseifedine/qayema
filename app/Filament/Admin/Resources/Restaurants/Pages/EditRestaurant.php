<?php

namespace App\Filament\Admin\Resources\Restaurants\Pages;

use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRestaurant extends EditRecord
{
    use KeepsTranslations;

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
            DeleteAction::make()
                ->modalHeading('Delete restaurant and owner')
                ->modalDescription(RestaurantResource::DELETE_WARNING),
        ];
    }

    /** Open the package fields on what the restaurant has, and the text in every language. */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        return $this->fillTranslations([...$data, ...PackageFields::stateFor($this->record)]);
    }

    /**
     * Turn "for how long" into an end date, hand the note to the history, and
     * keep the text the form does not show (the menu's other language).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $data['package_ends_at'] = PackageFields::endsAt($data);
        $this->record->packageChangeNote = $data['note'] ?? null;

        return $this->mergeTranslations(collect($data)->except(PackageFields::EXTRA)->all());
    }
}
