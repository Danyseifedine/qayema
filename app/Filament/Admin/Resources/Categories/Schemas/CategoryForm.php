<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

use App\Filament\Admin\Schemas\Components\MenuTextInputs;
use App\Support\CategoryIcons;
use App\Support\MenuTextLimits;
use Filament\Forms\Components\Select;
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
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->preload()
                            ->required()
                            // The name is written in this restaurant's main language.
                            ->live()
                            ->helperText('The restaurant this category belongs to.'),
                        // Menu text in the restaurant's main language. The
                        // other languages are kept on save (KeepsTranslations).
                        ...MenuTextInputs::make(fn (string $code, string $language): TextInput => TextInput::make("name.{$code}")
                            ->label("Name ({$language})")
                            ->placeholder('e.g. Starters, Mains, Desserts')
                            ->required()
                            ->maxLength(MenuTextLimits::CATEGORY_NAME)
                            ->helperText('Displayed as a section heading on the public menu.'), MenuTextInputs::ofChosenRestaurant()),
                        ...MenuTextInputs::make(fn (string $code, string $language): Textarea => Textarea::make("description.{$code}")
                            ->label("Description ({$language})")
                            ->placeholder('e.g. Served from noon until close')
                            ->rows(2)
                            ->maxLength(MenuTextLimits::CATEGORY_DESCRIPTION)
                            ->helperText('Optional. One line under the heading on the public menu.')
                            ->columnSpanFull(), MenuTextInputs::ofChosenRestaurant()),
                        Select::make('icon')
                            ->label('Icon')
                            ->options(CategoryIcons::labels())
                            ->searchable()
                            ->placeholder('No icon')
                            ->helperText('Optional. Shown before the category\'s name in the menu\'s tabs.'),
                        TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->required()
                            ->placeholder('0')
                            ->helperText('Lower numbers appear first. Use 0, 1, 2…'),
                    ]),

            ]);
    }
}
