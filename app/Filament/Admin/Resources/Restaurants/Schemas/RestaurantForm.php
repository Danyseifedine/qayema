<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Basic Information')
                    ->description('Owner, name, and public URL of the restaurant.')
                    ->columns(2)
                    ->schema([
                        Select::make('user_id')
                            ->label('Owner')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->helperText('The user account that owns this restaurant.'),
                        TextInput::make('name')
                            ->placeholder('e.g. The Golden Spoon')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug((string) ($state ?? ''))))
                            ->helperText('Shown on the public menu page.'),
                        TextInput::make('slug')
                            ->placeholder('the-golden-spoon')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug((string) ($state ?? ''))))
                            ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Str::slug((string) ($state ?? '')))
                            ->helperText('Used in the public URL. Lowercase letters, numbers and hyphens only — invalid characters are removed automatically.')
                            ->columnSpanFull(),
                        Textarea::make('description')
                            ->placeholder('A short description of your restaurant…')
                            ->rows(3)
                            ->helperText('Optional. Shown on the public menu page.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Contact')
                    ->description('Phone number displayed on the public menu.')
                    ->schema([
                        PhoneInput::make('phone')
                            ->label('Phone Number')
                            ->defaultCountry('LB')
                            ->displayNumberFormat(PhoneInputNumberType::INTERNATIONAL)
                            ->inputNumberFormat(PhoneInputNumberType::E164)
                            ->countrySearch(true)
                            ->helperText('Select the country flag to set the dial code, then enter the number. Shown when "Show phone" is enabled.')
                            ->columnSpanFull(),
                    ]),

                Section::make('Template & Limits')
                    ->description('Assign a display template and set the content limits for this restaurant.')
                    ->columns(3)
                    ->schema([
                        Select::make('template_id')
                            ->label('Template')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->placeholder('No template')
                            ->helperText('Controls the public menu design.')
                            ->columnSpan(3),
                        TextInput::make('dish_limit')
                            ->label('Dish Limit')
                            ->numeric()
                            ->required()
                            ->default(40)
                            ->minValue(1)
                            ->helperText('Max dishes allowed.'),
                        TextInput::make('category_limit')
                            ->label('Category Limit')
                            ->numeric()
                            ->required()
                            ->default(10)
                            ->minValue(1)
                            ->helperText('Max categories allowed.'),
                        TextInput::make('social_link_limit')
                            ->label('Social Link Limit')
                            ->numeric()
                            ->required()
                            ->default(4)
                            ->minValue(1)
                            ->helperText('Max social links allowed.'),
                    ]),

                Section::make('Visibility')
                    ->schema([
                        Toggle::make('is_active')
                            ->label('Restaurant is active (visible to the public)')
                            ->default(true)
                            ->helperText('When off, the public menu link will not load for visitors.'),
                    ]),

                Section::make('Media')
                    ->description('Logo and cover image are shown on the public menu.')
                    ->schema([
                        SpatieMediaLibraryFileUpload::make('logo')
                            ->label('Logo')
                            ->collection('logo')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Square image recommended. Max 5 MB.'),
                        SpatieMediaLibraryFileUpload::make('cover_image')
                            ->label('Cover Image')
                            ->collection('cover_image')
                            ->image()
                            ->maxSize(5120)
                            ->helperText('Recommended 1920×600 px. Max 5 MB.'),
                    ]),
            ]);
    }
}
