<?php

namespace App\Filament\Admin\Resources\CoinPacks;

use App\Filament\Admin\Resources\CoinPacks\Pages\CreateCoinPack;
use App\Filament\Admin\Resources\CoinPacks\Pages\EditCoinPack;
use App\Filament\Admin\Resources\CoinPacks\Pages\ListCoinPacks;
use App\Filament\Admin\Resources\CoinPacks\Schemas\CoinPackForm;
use App\Filament\Admin\Resources\CoinPacks\Tables\CoinPacksTable;
use App\Models\CoinPack;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CoinPackResource extends Resource
{
    protected static ?string $model = CoinPack::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $recordTitleAttribute = 'slug';

    protected static ?string $navigationLabel = 'Coin Packs';

    protected static UnitEnum|string|null $navigationGroup = 'System';

    public static function form(Schema $schema): Schema
    {
        return CoinPackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CoinPacksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCoinPacks::route('/'),
            'create' => CreateCoinPack::route('/create'),
            'edit' => EditCoinPack::route('/{record}/edit'),
        ];
    }
}
