<?php

namespace App\Filament\Admin\Resources\RestaurantStatistics;

use App\Filament\Admin\Resources\RestaurantStatistics\Pages\ListRestaurantStatistics;
use App\Filament\Admin\Resources\RestaurantStatistics\Pages\ViewRestaurantStatistic;
use App\Filament\Admin\Resources\RestaurantStatistics\Tables\RestaurantStatisticsTable;
use App\Models\RestaurantStatistic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantStatisticResource extends Resource
{
    protected static ?string $model = RestaurantStatistic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Statistics';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return RestaurantStatisticsTable::configure($table);
    }

    public static function form(Schema $schema): Schema
    {
        return $schema;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantStatistics::route('/'),
            'view' => ViewRestaurantStatistic::route('/{record}'),
        ];
    }
}
