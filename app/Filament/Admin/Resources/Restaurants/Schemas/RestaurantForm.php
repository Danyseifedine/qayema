<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use Filament\Forms\Components\DateTimePicker;
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
                            ->unique(ignoreRecord: true)
                            ->validationMessages(['unique' => 'This owner already has a restaurant.'])
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

                Section::make('Package')
                    ->description('The plan this restaurant\'s limits and features come from. Extra slots granted below stack on top of it.')
                    ->columns(3)
                    ->schema([
                        Select::make('package_id')
                            ->label('Package')
                            ->relationship('package', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->preload()
                            ->required()
                            ->default(fn (): ?int => \App\Models\Package::default()?->id),
                        DateTimePicker::make('package_started_at')
                            ->label('Started at')
                            ->helperText('When this package was assigned.'),
                        DateTimePicker::make('package_ends_at')
                            ->label('Expires at')
                            ->helperText('Leave empty for no expiry. Past this date the restaurant falls back to the default package.'),
                    ]),

                Section::make('Template')
                    ->description('The public menu design. Templates are pure design — every active one is available on every package.')
                    ->schema([
                        Select::make('template_id')
                            ->label('Template')
                            ->relationship('template', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                            ->preload()
                            ->nullable()
                            ->placeholder('No template')
                            ->helperText('The design the public menu renders with. The owner can change it themselves.'),
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
