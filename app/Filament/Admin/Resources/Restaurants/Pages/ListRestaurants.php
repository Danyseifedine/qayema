<?php

namespace App\Filament\Admin\Resources\Restaurants\Pages;

use App\Filament\Admin\Resources\Restaurants\RestaurantResource;
use App\Models\Restaurant;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class ListRestaurants extends ListRecords
{
    protected static string $resource = RestaurantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    /** The lists an admin asks for most, one click each; the filters narrow any of them. */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All'),
            'renew' => Tab::make('To renew')
                ->icon(Heroicon::OutlinedClock)
                ->badge(fn (): ?int => RestaurantResource::toRenewCount() ?: null)
                ->badgeColor('danger')
                ->modifyQueryUsing(fn (Builder $query): Builder => RestaurantResource::toRenew($query)),
            'ended' => Tab::make('Ended')
                ->icon(Heroicon::OutlinedExclamationTriangle)
                ->modifyQueryUsing(fn (Builder $query): Builder => self::packageExpired($query)),
            'paying' => Tab::make('Paying')
                ->icon(Heroicon::OutlinedBanknotes)
                ->modifyQueryUsing(fn (Builder $query): Builder => self::paying($query)),
            'whish' => Tab::make('Whish')
                ->icon(Heroicon::OutlinedDevicePhoneMobile)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('pays_with_whish', true)),
            'off' => Tab::make('Switched off')
                ->icon(Heroicon::OutlinedEyeSlash)
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_active', false)),
        ];
    }

    /**
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    private static function packageExpired(Builder $query): Builder
    {
        return $query->packageExpired();
    }

    /**
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    private static function paying(Builder $query): Builder
    {
        return $query->paying();
    }
}
