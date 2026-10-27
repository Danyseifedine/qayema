<?php

namespace App\Filament\Admin\Resources\RestaurantSocialLinks;

use App\Filament\Admin\Resources\RestaurantSocialLinks\Pages\CreateRestaurantSocialLink;
use App\Filament\Admin\Resources\RestaurantSocialLinks\Pages\EditRestaurantSocialLink;
use App\Filament\Admin\Resources\RestaurantSocialLinks\Pages\ListRestaurantSocialLinks;
use App\Filament\Admin\Resources\RestaurantSocialLinks\Schemas\RestaurantSocialLinkForm;
use App\Filament\Admin\Resources\RestaurantSocialLinks\Tables\RestaurantSocialLinksTable;
use App\Models\RestaurantSocialLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class RestaurantSocialLinkResource extends Resource
{
    protected static ?string $model = RestaurantSocialLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    /** Found through its restaurant; the top bar's search would only repeat names. */
    protected static bool $isGloballySearchable = false;

    protected static ?string $recordTitleAttribute = 'platform';

    protected static ?string $navigationLabel = 'Social links';

    protected static UnitEnum|string|null $navigationGroup = 'Menu content';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return RestaurantSocialLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantSocialLinksTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRestaurantSocialLinks::route('/'),
            'create' => CreateRestaurantSocialLink::route('/create'),
            'edit' => EditRestaurantSocialLink::route('/{record}/edit'),
        ];
    }
}
