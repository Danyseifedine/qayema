<?php

namespace App\Filament\Admin\Resources\Restaurants\Pages;

use App\Filament\Admin\Concerns\KeepsTranslations;
use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Filament\Admin\Resources\Restaurants\Schemas\PackageFields;
use App\Models\Restaurant;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateRestaurant extends CreateRecord
{
    use KeepsTranslations;

    protected static string $resource = RestaurantResource::class;

    private ?string $packageNote = null;

    /** Turn "for how long" into an end date, and keep the note for the history. */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['package_ends_at'] = PackageFields::endsAt($data);
        $this->packageNote = $data['note'] ?? null;

        return $this->mergeTranslations(collect($data)->except(PackageFields::EXTRA)->all());
    }

    protected function handleRecordCreation(array $data): Model
    {
        $restaurant = new Restaurant($data);
        $restaurant->packageChangeNote = $this->packageNote;
        $restaurant->save();

        return $restaurant;
    }
}
