<?php

namespace App\Filament\Admin\Resources\Dishes\Schemas;

use App\Models\Category;
use App\Models\Menu;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class DishForm
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
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set) => $set('category_id', null)),
                Select::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'name', fn ($query, $get) => 
                        $query->where('menu_id', $get('menu_id'))
                    )
                    ->searchable()
                    ->preload()
                    ->nullable(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                TextInput::make('price')
                    ->numeric()
                    ->prefix('$')
                    ->step(0.01)
                    ->minValue(0)
                    ->helperText('Leave empty for home cook menus'),
                TextInput::make('serving_size')
                    ->label('Serving Size')
                    ->maxLength(255)
                    ->helperText('e.g., "2-3 people", "Large"'),
                TextInput::make('prep_time')
                    ->label('Preparation Time (minutes)')
                    ->numeric()
                    ->minValue(0),
                TextInput::make('display_order')
                    ->label('Display Order')
                    ->numeric()
                    ->default(0)
                    ->required(),
                Textarea::make('ingredients')
                    ->label('Ingredients')
                    ->rows(4)
                    ->helperText('List all ingredients, separated by commas or new lines')
                    ->columnSpanFull(),
                TagsInput::make('allergens')
                    ->label('Allergens')
                    ->placeholder('Add allergen')
                    ->helperText('Press Enter to add each allergen (e.g., Gluten, Dairy, Nuts)')
                    ->columnSpanFull(),
                SpatieMediaLibraryFileUpload::make('images')
                    ->label('Dish Images')
                    ->collection('images')
                    ->multiple()
                    ->image()
                    ->maxSize(5120)
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        null,
                        '16:9',
                        '4:3',
                        '1:1',
                    ])
                    ->helperText('Upload one or more images for this dish (max 5MB each)')
                    ->columnSpanFull(),
                Toggle::make('is_available')
                    ->label('Available')
                    ->default(true)
                    ->helperText('Unavailable dishes will be hidden from public view'),
            ]);
    }
}
