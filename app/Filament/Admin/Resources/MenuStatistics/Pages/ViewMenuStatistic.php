<?php

namespace App\Filament\Admin\Resources\MenuStatistics\Pages;

use App\Filament\Admin\Resources\MenuStatistics\MenuStatisticResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewMenuStatistic extends ViewRecord
{
    protected static string $resource = MenuStatisticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
