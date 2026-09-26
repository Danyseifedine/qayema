<?php

namespace App\Filament\Admin\Resources\Categories\Schemas;

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
                            ->helperText('The restaurant this category belongs to.'),
                        TextInput::make('name')
                            ->placeholder('e.g. Starters, Mains, Desserts')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Displayed as a section heading on the public menu.'),
                        Textarea::make('description')
                            ->placeholder('e.g. Served from noon until close')
                            ->rows(2)
                            ->maxLength(300)
                            ->helperText('Optional. One line under the heading on the public menu.')
                            ->columnSpanFull(),
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
