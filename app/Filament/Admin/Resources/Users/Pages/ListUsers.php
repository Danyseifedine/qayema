<?php

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Enums\UserRole;
use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** Owners, admins, and the sign-ups that stopped before the end of setup. */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'owners' => Tab::make('Owners')
                ->icon(Heroicon::OutlinedBuildingStorefront)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('role', UserRole::MenuOwner)),
            'setup' => Tab::make('Setup not finished')
                ->icon(Heroicon::OutlinedClock)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query
                    ->where('role', UserRole::MenuOwner)
                    ->whereNull('onboarding_completed_at')),
            'admins' => Tab::make('Admins')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('role', UserRole::Admin)),
        ];
    }
}
