<?php

namespace App\Filament\Admin\Resources\MenuSocialLinks;

use App\Filament\Admin\Resources\MenuSocialLinks\Pages\CreateMenuSocialLink;
use App\Filament\Admin\Resources\MenuSocialLinks\Pages\EditMenuSocialLink;
use App\Filament\Admin\Resources\MenuSocialLinks\Pages\ListMenuSocialLinks;
use App\Filament\Admin\Resources\MenuSocialLinks\Schemas\MenuSocialLinkForm;
use App\Filament\Admin\Resources\MenuSocialLinks\Tables\MenuSocialLinksTable;
use App\Models\MenuSocialLink;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MenuSocialLinkResource extends Resource
{
    protected static ?string $model = MenuSocialLink::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static ?string $recordTitleAttribute = 'platform';

    protected static ?string $navigationLabel = 'Social Links';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return MenuSocialLinkForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuSocialLinksTable::configure($table);
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
            'index' => ListMenuSocialLinks::route('/'),
            'create' => CreateMenuSocialLink::route('/create'),
            'edit' => EditMenuSocialLink::route('/{record}/edit'),
        ];
    }
}
