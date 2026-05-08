<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Category Details')
                    ->description('Basic information about this menu category.')
                    ->columns(2)
                    ->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('The restaurant this category belongs to.'),
                        TextInput::make('name')
                            ->placeholder('e.g. Starters, Mains, Desserts')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Displayed as a section heading on the public menu.'),
                        Textarea::make('description')
                            ->placeholder('Optional description shown under the category name…')
                            ->rows(3)
                            ->helperText('Optional. Visible on the public menu.')
                            ->columnSpanFull(),
                        TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->placeholder('0')
                            ->helperText('Lower numbers appear first. Use 0, 1, 2…'),
                    ]),

                Section::make('Category Image')
                    ->description('Shown when "Show category images" is enabled in restaurant settings.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('image')
                            ->label('Image')
                            ->collection('image')
                            ->image()
                            ->maxSize(5120)
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions([null, '16:9', '4:3', '1:1'])
                            ->helperText('Max 5 MB. Will be optimised automatically.'),
                    ]),
            ]);
    }
}
