<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use App\Enums\Feature;
use App\Enums\PackageStatus;
use App\Filament\Admin\Schemas\Components\MenuTextInputs;
use App\Models\Restaurant;
use App\Rules\AvailableSlug;
use App\Services\Menu\MenuLanguages;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class RestaurantForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(['lg' => 3])
            ->components([
                // A wide column for what defines it, a narrow one for its settings.
                Group::make([
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
                            // The language every name on the menu is written in.
                            // Picking the second one swaps the two (EditRestaurant).
                            Select::make('main_locale')
                                ->label('Main language')
                                ->options(MenuLanguages::options())
                                ->default(MenuLanguages::DEFAULT_MAIN)
                                ->required()
                                ->live()
                                ->helperText('Names are required in it, and it is the only language shown while the second one is off.'),
                            // Menu text is kept per language; the admin edits the
                            // main one, and the other languages are kept
                            // (EditRestaurant merges them).
                            ...MenuTextInputs::make(fn (string $code, string $language): TextInput => TextInput::make("name.{$code}")
                                ->label("Name ({$language})")
                                ->placeholder('e.g. The Golden Spoon')
                                ->required()
                                ->maxLength(255)
                                // Only a new restaurant takes its address from its
                                // name: once live, the slug is on printed QR codes.
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set, string $operation) => $operation === 'create'
                                    ? $set('slug', \Illuminate\Support\Str::slug((string) ($state ?? '')))
                                    : null)
                                ->helperText('Shown on the public menu page.'), fn (Get $get): ?string => $get('main_locale')),
                            TextInput::make('slug')
                                ->placeholder('the-golden-spoon')
                                ->required()
                                // Not another restaurant's, nor its former link
                                // (that one forwards printed QR codes).
                                ->rules(fn (?Restaurant $record): array => [new AvailableSlug($record?->id)])
                                ->maxLength(255)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug((string) ($state ?? ''))))
                                ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Str::slug((string) ($state ?? '')))
                                ->helperText('Used in the public URL. Lowercase letters, numbers and hyphens only. Invalid characters are removed automatically. A changed link keeps the old one forwarding here.')
                                ->columnSpanFull(),
                            ...MenuTextInputs::make(fn (string $code, string $language): Textarea => Textarea::make("description.{$code}")
                                ->label("Description ({$language})")
                                ->placeholder('A short description of your restaurant…')
                                ->rows(3)
                                ->helperText('Optional. Shown on the public menu page.')
                                ->columnSpanFull(), fn (Get $get): ?string => $get('main_locale')),
                        ]),
                    Section::make('Package')
                        ->description('The package this restaurant\'s limits and features come from, and for how long. Extra slots & add-ons below stack on top of it; every change is kept in the package history.')
                        ->columns(2)
                        ->schema([
                            ...PackageFields::components(),
                            TextEntry::make('gets_now')
                                ->label('What it gets now')
                                ->state(fn (?Restaurant $record): ?string => $record === null ? null : self::entitlementsSummary($record))
                                ->visibleOn('edit')
                                ->columnSpanFull(),
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
                ])->columnSpan(['lg' => 2]),
                Group::make([
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
                    Section::make('Template')
                        ->description('The public menu design. A design marked premium needs a package with premium designs; without one the menu shows the first free design and keeps this choice.')
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
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    /**
     * The package in force and every feature it resolves to with grants, as
     * one line an admin can read at a glance.
     */
    private static function entitlementsSummary(Restaurant $restaurant): string
    {
        $entitlements = $restaurant->entitlements();

        $features = collect(Feature::cases())
            ->map(fn (Feature $feature): ?string => match (true) {
                $feature->isLimit() => $feature->label().' '.($entitlements->limit($feature) ?? '∞'),
                $entitlements->can($feature) => $feature->label(),
                default => null,
            })
            ->filter()
            ->implode(' · ');

        $status = match ($restaurant->packageStatus()) {
            PackageStatus::Scheduled => $restaurant->package?->name.' starts '.$restaurant->package_started_at->toFormattedDayDateString().'; until then '.$restaurant->effectivePackage()?->name,
            PackageStatus::Expired => $restaurant->package?->name.' ended '.$restaurant->package_ends_at->toFormattedDayDateString().'; now '.$restaurant->effectivePackage()?->name,
            default => $restaurant->package?->name.($restaurant->package_ends_at === null ? ', forever' : ' until '.$restaurant->package_ends_at->toFormattedDayDateString()),
        };

        return $status.': '.$features;
    }
}
