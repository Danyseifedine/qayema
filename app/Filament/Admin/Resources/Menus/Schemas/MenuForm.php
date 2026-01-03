<?php

namespace App\Filament\Admin\Resources\Menus\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class MenuForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('user_id')
                    ->label('Owner')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->live(onBlur: true)
                    ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug($state))),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255)
                    ->disabled()
                    ->dehydrated()
                    ->helperText('URL-friendly version of the name (auto-generated)'),
                Textarea::make('description')
                    ->rows(3)
                    ->columnSpanFull(),
                Select::make('menu_style')
                    ->label('Menu Style')
                    ->options([
                        'restaurant' => 'Restaurant',
                        'home' => 'Home Cook',
                    ])
                    ->searchable()
                    ->required()
                    ->default('home'),
                TextInput::make('dish_limit')
                    ->label('Dish Limit')
                    ->numeric()
                    ->required()
                    ->default(40)
                    ->minValue(1)
                    ->helperText('Maximum number of dishes allowed for this menu'),
                TextInput::make('category_limit')
                    ->label('Category Limit')
                    ->numeric()
                    ->required()
                    ->default(10)
                    ->minValue(1)
                    ->helperText('Maximum number of categories allowed for this menu'),
                TextInput::make('social_link_limit')
                    ->label('Social Link Limit')
                    ->numeric()
                    ->required()
                    ->default(4)
                    ->minValue(1)
                    ->helperText('Maximum number of social links allowed for this menu'),
                Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->helperText('Only active menus will be visible to the public'),
            ]);
    }
}
