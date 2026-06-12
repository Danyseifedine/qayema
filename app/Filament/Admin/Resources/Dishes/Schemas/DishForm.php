<?php

namespace App\Filament\Admin\Resources\Dishes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class DishForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Assignment')
                    ->description('Which restaurant and category this dish belongs to.')
                    ->columns(2)
                    ->schema([
                        Select::make('restaurant_id')
                            ->label('Restaurant')
                            ->relationship('restaurant', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (callable $set) => $set('category_id', null))
                            ->helperText('Select the restaurant first to filter categories.'),
                        Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'name', fn ($query, $get) => $query->where('restaurant_id', $get('restaurant_id')))
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->preload()
                            ->nullable()
                            ->helperText('Optional. Groups this dish under a category.'),
                    ]),

                Section::make('Dish Details')
                    ->description('Name, price, description and ingredients.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->placeholder('e.g. Grilled Salmon')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Displayed on the public menu.'),
                        TextInput::make('price')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00')
                            ->step(0.01)
                            ->minValue(0)
                            ->helperText('Leave empty to hide the price.'),
                        Textarea::make('description')
                            ->placeholder('A short description of the dish…')
                            ->rows(3)
                            ->helperText('Optional. Shown below the dish name.')
                            ->columnSpanFull(),
                        Textarea::make('ingredients')
                            ->label('Ingredients')
                            ->placeholder('e.g. Salmon, lemon, garlic, olive oil…')
                            ->rows(3)
                            ->helperText('Optional. Shown when "Show ingredients" is enabled.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Tags')
                    ->description('Assign dietary, cuisine, vibe or style tags to this dish.')
                    ->schema([
                        Select::make('tags')
                            ->relationship('tags', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->multiple()
                            ->preload()
                            ->helperText('Tags help customers filter dishes. Managed under System → Tags.'),
                    ]),

                Section::make('Display & Availability')
                    ->columns(2)
                    ->schema([
                        TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->placeholder('0')
                            ->helperText('Lower numbers appear first within the category.'),
                        Toggle::make('is_available')
                            ->label('Dish is available (visible to customers)')
                            ->default(true)
                            ->helperText('Uncheck to hide this dish without deleting it.'),
                    ]),

                Section::make('Images')
                    ->description('Photos shown on the public menu. First image is the main one.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('images')
                            ->label('Dish Images')
                            ->collection('images')
                            ->multiple()
                            ->image()
                            ->maxSize(5120)
                            ->imageEditor()
                            ->imageEditorAspectRatioOptions([null, '16:9', '4:3', '1:1'])
                            ->helperText('Upload one or more images. Max 5 MB each. Optimised automatically.'),
                    ]),
            ]);
    }
}
