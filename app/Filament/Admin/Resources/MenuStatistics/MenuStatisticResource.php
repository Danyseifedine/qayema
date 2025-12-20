<?php

namespace App\Filament\Admin\Resources\MenuStatistics;

use App\Filament\Admin\Resources\MenuStatistics\Pages\CreateMenuStatistic;
use App\Filament\Admin\Resources\MenuStatistics\Pages\EditMenuStatistic;
use App\Filament\Admin\Resources\MenuStatistics\Pages\ListMenuStatistics;
use App\Filament\Admin\Resources\MenuStatistics\Pages\ViewMenuStatistic;
use App\Filament\Admin\Resources\MenuStatistics\Schemas\MenuStatisticForm;
use App\Filament\Admin\Resources\MenuStatistics\Schemas\MenuStatisticInfolist;
use App\Filament\Admin\Resources\MenuStatistics\Tables\MenuStatisticsTable;
use App\Models\MenuStatistic;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MenuStatisticResource extends Resource
{
    protected static ?string $model = MenuStatistic::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    protected static ?string $recordTitleAttribute = 'id';

    protected static ?string $navigationLabel = 'Menu Statistics';

    protected static ?int $navigationSort = 10;

    public static function canCreate(): bool
    {
        return false; // Statistics are created automatically, not manually
    }

    public static function form(Schema $schema): Schema
    {
        return MenuStatisticForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return MenuStatisticInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuStatisticsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMenuStatistics::route('/'),
            'view' => ViewMenuStatistic::route('/{record}'),
        ];
    }
}
