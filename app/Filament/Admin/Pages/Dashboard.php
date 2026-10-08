<?php

namespace App\Filament\Admin\Pages;

use App\Filament\Admin\Actions\SendTestNotificationAction;
use Filament\Pages\Dashboard as BaseDashboard;

/**
 * The admin home: Filament's dashboard (the packages ending soon), with a
 * test notification to the admin app's phones at the top.
 */
class Dashboard extends BaseDashboard
{
    protected function getHeaderActions(): array
    {
        return [
            SendTestNotificationAction::make(),
        ];
    }
}
