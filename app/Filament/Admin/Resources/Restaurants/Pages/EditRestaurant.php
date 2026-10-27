<?php

namespace App\Filament\Admin\Resources\Restaurants\Pages;

use App\Filament\Admin\Actions\ExtendPackageAction;
use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use App\Services\Packages\RestaurantFeatures;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;

class EditRestaurant extends EditRecord
{
    use KeepsTranslations;

    protected static string $resource = RestaurantResource::class;

    /**
     * What was changed in the Features section, written after the restaurant
     * (RestaurantFeatures::save()); never a restaurant column.
     *
     * @var array<string, mixed>|null
     */
    private ?array $features = null;

    /** One page with tabs: the details first, then overrides, history, orders and the menu. */
    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Details';
    }

    public function getContentTabIcon(): Heroicon
    {
        return Heroicon::OutlinedBuildingStorefront;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('menu')
                ->label('Open menu')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (Restaurant $record): string => route('public.menu', $record->slug))
                ->openUrlInNewTab(),
            // The form holds the package fields too: they are filled again
            // so a later save does not put the old end date back.
            ExtendPackageAction::make()
                ->color('gray')
                ->label('Extend package')
                ->after(fn () => $this->fillForm()),
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
        return $this->fillTranslations([
            ...$data,
            ...PackageFields::stateFor($this->record),
            'features' => RestaurantFeatures::state($this->restaurant()),
        ]);
    }

    /**
     * Turn "for how long" into an end date, hand the note to the history, and
     * keep the text the form does not show (the menu's other language).
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Only what was changed, read before the package may change.
        $this->features = RestaurantFeatures::changed($this->restaurant(), (array) ($data['features'] ?? []));
        unset($data['features']);
        $data['package_ends_at'] = PackageFields::endsAt($data);
        $this->record->packageChangeNote = $data['note'] ?? null;

        // A new main language: the second one becomes the old main when they
        // swap, and the menu keeps opening in the main one if it did. The
        // same language again changes nothing.
        if (isset($data['main_locale'])) {
            $data = [...$data, ...MenuLanguages::withMain($this->record, $data['main_locale'])];
        }

        return $this->mergeTranslations(collect($data)->except(PackageFields::EXTRA)->all());
    }

    private function restaurant(): Restaurant
    {
        $record = $this->getRecord();

        if (! $record instanceof Restaurant) {
            throw new \LogicException('The restaurant page edits a restaurant.');
        }

        return $record;
    }

    /**
     * What the admin changed in Features, on top of the package the save may
     * have just changed; the form opens again on what holds now.
     */
    protected function afterSave(): void
    {
        if ($this->features !== null && $this->features !== []) {
            RestaurantFeatures::save($this->restaurant()->fresh() ?? $this->restaurant(), $this->features);
            $this->features = null;
        }

        $this->fillForm();
    }
}
