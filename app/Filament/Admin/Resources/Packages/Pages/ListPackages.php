<?php

namespace App\Filament\Admin\Resources\Packages\Pages;

use App\Filament\Admin\Resources\Packages\PackageResource;
use Filament\Resources\Pages\ListRecords;

class ListPackages extends ListRecords
{
    protected static string $resource = PackageResource::class;

    public function getSubheading(): ?string
    {
        return 'What each plan includes. A change here reaches every restaurant on that package immediately.';
    }
}
