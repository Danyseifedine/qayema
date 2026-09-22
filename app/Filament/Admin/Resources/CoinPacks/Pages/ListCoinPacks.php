<?php

namespace App\Filament\Admin\Resources\CoinPacks\Pages;

use App\Filament\Admin\Resources\CoinPacks\CoinPackResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCoinPacks extends ListRecords
{
    protected static string $resource = CoinPackResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
