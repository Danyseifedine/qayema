<?php

namespace App\Filament\Admin\Resources\MenuSettings\Schemas;

use App\Models\Menu;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class MenuSettingForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('menu_id')
                    ->label('Menu')
                    ->relationship('menu', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->columnSpanFull(),
                ColorPicker::make('theme_color')
                    ->label('Theme Color')
                    ->default('#3b82f6')
                    ->helperText('Primary color for the menu theme'),
                SpatieMediaLibraryFileUpload::make('logo')
                    ->label('Logo')
                    ->collection('logo')
                    ->image()
                    ->maxSize(2048)
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '1:1',
                        '16:9',
                        '4:3',
                    ])
                    ->helperText('Upload menu logo (max 2MB)'),
                SpatieMediaLibraryFileUpload::make('cover_image')
                    ->label('Cover Image')
                    ->collection('cover_image')
                    ->image()
                    ->maxSize(5120)
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '16:9',
                        '21:9',
                        null,
                    ])
                    ->helperText('Upload cover image for the menu (max 5MB)')
                    ->columnSpanFull(),
                TextInput::make('currency')
                    ->label('Currency')
                    ->default('USD')
                    ->maxLength(3)
                    ->required()
                    ->helperText('Currency code (e.g., USD, EUR, GBP)'),
                Select::make('language')
                    ->label('Language')
                    ->options([
                        'en' => 'English',
                        'fr' => 'French',
                        'es' => 'Spanish',
                        'ar' => 'Arabic',
                        'de' => 'German',
                    ])
                    ->searchable()
                    ->default('en')
                    ->required(),
            ]);
    }
}
