<?php

namespace App\Filament\Admin\Resources\Dishes;

use App\Filament\Admin\Resources\Dishes\Pages\CreateDish;
use App\Filament\Admin\Resources\Dishes\Pages\EditDish;
use App\Filament\Admin\Resources\Dishes\Pages\ListDishes;
use App\Filament\Admin\Resources\Dishes\Schemas\DishForm;
use App\Filament\Admin\Resources\Dishes\Tables\DishesTable;
use App\Models\Dish;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class DishResource extends Resource
{
    protected static ?string $model = Dish::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Dishes';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return DishForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DishesTable::configure($table);
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
            'index' => ListDishes::route('/'),
            'create' => CreateDish::route('/create'),
            'edit' => EditDish::route('/{record}/edit'),
        ];
    }
}
