<?php

namespace App\Filament\Admin\Resources\Dishes\Schemas;

use App\Models\Restaurant;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class DishForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 3])
            ->components([
                // A wide column for what defines it, a narrow one for its settings.
                Group::make([
                    Section::make('Dish Details')
                        ->description('Name, price and ingredients.')
                        ->columns(2)
                        ->schema([
                            // Menu text: the English, which every menu has. The
                            // other languages are kept on save (KeepsTranslations).
                            TextInput::make('name.en')
                                ->label('Name (English)')
                                ->placeholder('e.g. Grilled Salmon')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Displayed on the public menu.'),
                            TextInput::make('price')
                                ->numeric()
                                // The restaurant's own currency.
                                ->prefix(fn (Get $get): ?string => Restaurant::query()->find($get('restaurant_id'))?->currency)
                                ->placeholder('0.00')
                                ->step(0.01)
                                ->minValue(0)
                                ->helperText('Leave empty to hide the price.'),
                            Textarea::make('ingredients.en')
                                ->label('Ingredients (English)')
                                ->placeholder('e.g. Salmon, lemon, garlic, olive oil…')
                                ->rows(3)
                                ->helperText('Optional. Shown under the dish name on the menu.')
                                ->columnSpanFull(),
                        ]),
                    Section::make('Variants and add-ons')
                        ->description('Choices guests make on this dish. Each price is added to the dish price.')
                        ->collapsible()
                        ->schema([
                            // English names, like the rest of this form; a
                            // repeater row keeps the languages it does not show.
                            Repeater::make('variants')
                                ->label('Variants')
                                ->helperText('For example Size or Spice level. Guests pick one option of each.')
                                ->relationship()
                                ->orderColumn('display_order')
                                ->maxItems(config('menu.dish_options.variants'))
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => $state['name']['en'] ?? null)
                                ->addActionLabel('Add variant')
                                ->defaultItems(0)
                                ->schema([
                                    TextInput::make('name.en')
                                        ->label('Name (English)')
                                        ->placeholder('e.g. Size')
                                        ->required()
                                        ->maxLength(config('menu.dish_options.name_max')),
                                    Repeater::make('options')
                                        ->label('Options')
                                        ->relationship()
                                        ->orderColumn('display_order')
                                        ->minItems(2)
                                        ->maxItems(config('menu.dish_options.options'))
                                        ->defaultItems(2)
                                        ->addActionLabel('Add option')
                                        ->columns(2)
                                        ->schema([
                                            TextInput::make('name.en')
                                                ->label('Name (English)')
                                                ->placeholder('e.g. Large')
                                                ->required()
                                                ->maxLength(config('menu.dish_options.name_max')),
                                            self::extraPrice(),
                                        ]),
                                ]),
                            Repeater::make('addons')
                                ->label('Add-ons')
                                ->helperText('For example Extra cheese. Guests pick any number.')
                                ->relationship()
                                ->orderColumn('display_order')
                                ->maxItems(config('menu.dish_options.addons'))
                                ->addActionLabel('Add add-on')
                                ->defaultItems(0)
                                ->columns(2)
                                ->schema([
                                    TextInput::make('name.en')
                                        ->label('Name (English)')
                                        ->placeholder('e.g. Extra cheese')
                                        ->required()
                                        ->maxLength(config('menu.dish_options.name_max')),
                                    self::extraPrice(),
                                ]),
                        ]),
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
                ])->columnSpan(['lg' => 2]),
                Group::make([
                    Section::make('Image')
                        ->description('Photo shown on the public menu.')
                        ->schema([
                            SpatieMediaLibraryFileUpload::make('image')
                                ->label('Dish Image')
                                ->collection('image')
                                ->image()
                                ->maxSize(5120)
                                ->imageEditor()
                                ->imageEditorAspectRatioOptions([null, '16:9', '4:3', '1:1'])
                                ->helperText('Max 5 MB. Optimised automatically.'),
                        ]),
                    Section::make('Display & Availability')
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
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /** What a variant option or add-on adds to the dish's price. */
    private static function extraPrice(): TextInput
    {
        return TextInput::make('price')
            ->label('Adds to the price')
            ->numeric()
            ->default(0)
            ->required()
            ->step(0.01)
            ->minValue(0)
            ->maxValue(config('menu.dish_options.price_max'));
    }
}
