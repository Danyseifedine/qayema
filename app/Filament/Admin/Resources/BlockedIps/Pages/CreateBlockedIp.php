<?php

namespace App\Filament\Admin\Resources\BlockedIps\Pages;

use App\Filament\Admin\Resources\BlockedIps\BlockedIpResource;
use Filament\Resources\Pages\CreateRecord;

class CreateBlockedIp extends CreateRecord
{
    protected static string $resource = BlockedIpResource::class;
}
