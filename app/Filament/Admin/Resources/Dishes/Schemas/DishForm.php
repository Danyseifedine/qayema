<?php

namespace App\Filament\Admin\Resources\Dishes\Schemas;

use App\Filament\Admin\Schemas\Components\MenuTextInputs;
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
                            // Menu text in the restaurant's main language. The
                            // other languages are kept on save (KeepsTranslations).
                            ...MenuTextInputs::make(fn (string $code, string $language): TextInput => TextInput::make("name.{$code}")
                                ->label("Name ({$language})")
                                ->placeholder('e.g. Grilled Salmon')
                                ->required()
                                ->maxLength(255)
                                ->helperText('Displayed on the public menu.'), MenuTextInputs::ofChosenRestaurant()),
                            TextInput::make('price')
                                ->numeric()
                                // The restaurant's own currency.
                                ->prefix(fn (Get $get): ?string => Restaurant::query()->find($get('restaurant_id'))?->currency)
                                ->placeholder('0.00')
                                ->step(0.01)
                                ->minValue(0)
                                ->helperText('Leave empty to hide the price.'),
                            ...MenuTextInputs::make(fn (string $code, string $language): Textarea => Textarea::make("ingredients.{$code}")
                                ->label("Ingredients ({$language})")
                                ->placeholder('e.g. Salmon, lemon, garlic, olive oil…')
                                ->rows(3)
                                ->helperText('Optional. Shown under the dish name on the menu.')
                                ->columnSpanFull(), MenuTextInputs::ofChosenRestaurant()),
                        ]),
                    Section::make('Variants and add-ons')
                        ->description('Choices guests make on this dish. Each price is added to the dish price.')
                        ->collapsible()
                        ->schema([
                            // Names in the main language, like the rest of this
                            // form; a repeater row keeps the languages it does not show.
                            Repeater::make('variants')
                                ->label('Variants')
                                ->helperText('For example Size or Spice level. Guests pick one option of each.')
                                ->relationship()
                                ->orderColumn('display_order')
                                ->maxItems(config('menu.dish_options.variants'))
                                ->collapsible()
                                ->itemLabel(fn (array $state): ?string => collect((array) ($state['name'] ?? []))->filter()->first())
                                ->addActionLabel('Add variant')
                                ->defaultItems(0)
                                ->schema([
                                    ...self::choiceName('e.g. Size'),
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
                                            ...self::choiceName('e.g. Large'),
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
                                    ...self::choiceName('e.g. Extra cheese'),
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

    /**
     * A variant's, option's or add-on's name, in the restaurant's main language.
     *
     * @return array<int, TextInput>
     */
    private static function choiceName(string $placeholder): array
    {
        return MenuTextInputs::make(fn (string $code, string $language): TextInput => TextInput::make("name.{$code}")
            ->label("Name ({$language})")
            ->placeholder($placeholder)
            ->required()
            ->maxLength(config('menu.dish_options.name_max')), MenuTextInputs::ofChosenRestaurant());
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
