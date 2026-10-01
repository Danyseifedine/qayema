<?php

namespace App\Filament\Admin\Resources\Restaurants;

use App\Filament\Admin\Resources\Restaurants\Pages\CreateRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\EditRestaurant;
use App\Filament\Admin\Resources\Restaurants\Pages\ListRestaurants;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\FeatureGrantsRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\OrdersRelationManager;
use App\Filament\Admin\Resources\Restaurants\RelationManagers\PackageChangesRelationManager;
use App\Filament\Admin\Resources\Restaurants\Schemas\RestaurantForm;
use App\Filament\Admin\Resources\Restaurants\Tables\RestaurantsTable;
use App\Models\Restaurant;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RestaurantResource extends Resource
{
    protected static ?string $model = Restaurant::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingStorefront;

    protected static ?string $recordTitleAttribute = 'name';

    protected static ?string $navigationLabel = 'Restaurants';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return RestaurantForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantsTable::configure($table);
    }

    /** What deleting a restaurant takes with it, for every delete button. */
    public const DELETE_WARNING = 'This deletes the menu and the owner\'s account for good: every category, dish and photo, the logo and cover, social links, orders, statistics and package history, and the account they sign in with. This cannot be undone.';

    public static function getRelations(): array
    {
        return [
            FeatureGrantsRelationManager::class,
            PackageChangesRelationManager::class,
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurants::route('/'),
            'create' => CreateRestaurant::route('/create'),
            'edit' => EditRestaurant::route('/{record}/edit'),
        ];
    }
}
