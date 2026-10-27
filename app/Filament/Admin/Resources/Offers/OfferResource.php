<?php

namespace App\Filament\Admin\Resources\Offers;

use App\Filament\Admin\Resources\Offers\Pages\ListOffers;
use App\Filament\Admin\Resources\Offers\Tables\OffersTable;
use App\Models\Offer;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Every restaurant's offers, to see what runs where, pause one or delete
 * it. Owners write them in the dashboard, whose editor knows the schedule
 * and the menu languages; an admin changes one through "Log in as owner".
 */
class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedGift;

    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Offers';

    protected static UnitEnum|string|null $navigationGroup = 'Menu content';

    protected static ?int $navigationSort = 4;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return OffersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOffers::route('/'),
        ];
    }
}
