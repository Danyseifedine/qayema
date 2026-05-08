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

class RestaurantSocialLinkResource extends Resource
{
    protected static ?string $model = RestaurantSocialLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $recordTitleAttribute = 'platform';

    protected static ?string $navigationLabel = 'Social Links';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return RestaurantSocialLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RestaurantSocialLinksTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
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
