<?php

namespace App\Filament\Admin\Resources\MenuSettings;

use App\Filament\Admin\Resources\MenuSettings\Pages\CreateMenuSetting;
use App\Filament\Admin\Resources\MenuSettings\Pages\EditMenuSetting;
use App\Filament\Admin\Resources\MenuSettings\Pages\ListMenuSettings;
use App\Filament\Admin\Resources\MenuSettings\Schemas\MenuSettingForm;
use App\Filament\Admin\Resources\MenuSettings\Tables\MenuSettingsTable;
use App\Models\MenuSetting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class MenuSettingResource extends Resource
{
    protected static ?string $model = MenuSetting::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static ?string $recordTitleAttribute = 'setting.title';

    protected static ?string $navigationLabel = 'Menu Settings';

    protected static ?int $navigationSort = 6;

    public static function form(Schema $schema): Schema
    {
        return MenuSettingForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MenuSettingsTable::configure($table);
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
            'index' => ListMenuSettings::route('/'),
            'create' => CreateMenuSetting::route('/create'),
            'edit' => EditMenuSetting::route('/{record}/edit'),
        ];
    }
}
