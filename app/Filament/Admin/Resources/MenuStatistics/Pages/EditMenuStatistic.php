<?php

namespace App\Filament\Admin\Resources\MenuStatistics\Pages;

use App\Filament\Admin\Resources\MenuStatistics\MenuStatisticResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditMenuStatistic extends EditRecord
{
    protected static string $resource = MenuStatisticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
