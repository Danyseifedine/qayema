<?php

namespace App\Filament\Admin\Resources\Restaurants\Schemas;

use App\Enums\Feature;
use App\Enums\PackageStatus;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Filament\Admin\Schemas\Components\MenuTextInputs;
use App\Models\Package;
use App\Models\Restaurant;
use App\Rules\AvailableSlug;
use App\Services\Ai\WritingHelpCredits;
use App\Services\Ai\WritingHelpLog;
use App\Services\Ai\WritingHelpPrice;
use App\Services\Analytics\MenuTraffic;
use App\Services\Menu\MenuLanguages;
use App\Support\MenuTextLimits;
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
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Number;
use Ysfkaya\FilamentPhoneInput\Forms\PhoneInput;
use Ysfkaya\FilamentPhoneInput\PhoneInputNumberType;

class RestaurantForm
{
    /** The package picked in the form, read once a request per package. */
    private static function packageOf(mixed $packageId): ?Package
    {
        return filled($packageId) ? once(fn (): ?Package => Package::query()->find((int) $packageId)) : Package::default();
    }

    /** The package's own price, as the placeholder of the custom one. */
    private static function packagePrice(?Package $package): string
    {
        return match (true) {
            $package === null => '',
            $package->price_cents === null => 'Not published',
            default => Number::currency($package->price_cents / 100, $package->currency).' (the package price)',
        };
    }

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
                                ->maxLength(MenuTextLimits::RESTAURANT_NAME)
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
                                ->rules(fn (?Restaurant $record): array => AvailableSlug::rules($record?->id))
                                ->maxLength(AvailableSlug::MAX_LENGTH)
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn ($state, callable $set) => $set('slug', \Illuminate\Support\Str::slug((string) ($state ?? ''))))
                                ->dehydrateStateUsing(fn ($state) => \Illuminate\Support\Str::slug((string) ($state ?? '')))
                                ->helperText('Used in the public URL. Lowercase letters, numbers and hyphens only. Invalid characters are removed automatically. A changed link keeps the old one forwarding here.')
                                ->columnSpanFull(),
                            ...MenuTextInputs::make(fn (string $code, string $language): Textarea => Textarea::make("description.{$code}")
                                ->label("Description ({$language})")
                                ->placeholder('A short description of your restaurant…')
                                ->rows(3)
                                ->maxLength(MenuTextLimits::RESTAURANT_DESCRIPTION)
                                ->helperText('Optional. Shown on the public menu page.')
                                ->columnSpanFull(), fn (Get $get): ?string => $get('main_locale')),
                        ]),
                    Section::make('Package & billing')
                        ->icon(Heroicon::OutlinedCreditCard)
                        ->description('The package this restaurant\'s limits and features come from, for how long, and what it pays. The "Extras & overrides" tab changes single features on top of it; every change is kept in "Package history".')
                        ->columns(2)
                        ->schema([
                            ...PackageFields::components(),
                            TextInput::make('price_cents')
                                ->label('Price a month')
                                ->numeric()
                                ->minValue(0)
                                ->step(0.01)
                                ->prefix(fn (Get $get): string => self::packageOf($get('package_id'))->currency ?? 'USD')
                                ->placeholder(fn (Get $get): string => self::packagePrice(self::packageOf($get('package_id'))))
                                // Stored in cents; shown and typed in the currency.
                                ->formatStateUsing(fn (?int $state): ?string => $state === null ? null : number_format($state / 100, 2, '.', ''))
                                ->dehydrateStateUsing(fn (mixed $state): ?int => $state === null || $state === '' ? null : (int) round((float) $state * 100))
                                ->helperText('Only for this restaurant, and the owner sees it. Leave empty for the package price. Saved as you leave it here; the Change package actions clear it.'),
                            Toggle::make('pays_with_whish')
                                ->label('Pays with Whish')
                                ->helperText('For your records only.')
                                ->inline(false),
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
                    Section::make('At a glance')
                        ->icon(Heroicon::OutlinedChartBar)
                        ->visibleOn('edit')
                        ->schema([
                            TextEntry::make('glance_menu')
                                ->label('Menu')
                                ->state(fn (Restaurant $record): string => self::menuState($record))
                                ->badge()
                                ->color(fn (Restaurant $record): string => $record->menuOnline() ? 'success' : 'danger')
                                ->url(fn (Restaurant $record): string => route('public.menu', $record->slug))
                                ->openUrlInNewTab(),
                            TextEntry::make('glance_owner')
                                ->label('Owner')
                                ->state(fn (Restaurant $record): ?string => $record->user->email ?? $record->user->username)
                                ->placeholder('-')
                                ->copyable()
                                ->url(fn (Restaurant $record): ?string => $record->user === null ? null : UserResource::getUrl('view', ['record' => $record->user])),
                            TextEntry::make('glance_content')
                                ->label('Menu content')
                                ->state(fn (Restaurant $record): string => self::contentSummary($record)),
                            TextEntry::make('glance_traffic')
                                ->label('Visits and orders')
                                ->state(fn (Restaurant $record): string => self::trafficSummary($record)),
                            TextEntry::make('writing_help_used')
                                ->label('AI writing help')
                                ->state(fn (Restaurant $record): string => self::writingHelpSummary($record)),
                            TextEntry::make('ai_credits_month')
                                ->label('AI credits this month')
                                ->state(fn (Restaurant $record): string => self::creditsSummary($record)),
                        ]),
                    Section::make('Visibility')
                        ->schema([
                            Toggle::make('is_active')
                                ->label('Restaurant is active (visible to the public)')
                                ->default(true)
                                ->helperText('When off, the public menu link will not load for visitors. The menu also goes offline '.Restaurant::graceDays().' days after its package ends, until the package is extended or changed.'),
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
                    Section::make('Template')
                        ->description('The public menu design. A design marked premium needs a package with premium designs; without one the menu shows the first free design and keeps this choice.')
                        ->schema([
                            Select::make('template_id')
                                ->label('Template')
                                // Every restaurant's designs and the ones made for this one.
                                ->relationship('template', 'name', fn (Builder $query, ?Restaurant $record): Builder => $query->where(
                                    fn (Builder $open) => $open->whereNull('restaurant_id')->when($record !== null, fn (Builder $own) => $own->orWhere('restaurant_id', $record?->id)),
                                ))
                                ->getOptionLabelFromRecordUsing(fn ($record): string => (string) $record->name)
                                ->preload()
                                ->nullable()
                                ->placeholder('No template')
                                ->helperText('The design the public menu renders with. The owner can change it themselves.'),
                        ]),
                ])->columnSpan(['lg' => 1]),
                FeatureFields::section(),
            ]);
    }

    /** "Online", "Switched off", "Offline since 4 Sep (package ended)". */
    private static function menuState(Restaurant $restaurant): string
    {
        return match (true) {
            ! $restaurant->is_active => 'Switched off',
            $restaurant->menuOnline() => 'Online',
            default => 'Offline since '.$restaurant->offlineAt()?->toFormattedDateString().' (package ended)',
        };
    }

    /** "12 / 40 dishes · 3 / 8 categories · 1 / 1 links", counted in one query. */
    private static function contentSummary(Restaurant $restaurant): string
    {
        $restaurant->loadCount(['dishes', 'categories', 'socialLinks', 'orders']);

        $of = fn (int $count, Feature $limit, string $noun): string => Number::format($count).' / '.($restaurant->limitOf($limit) ?? '∞').' '.$noun;

        return implode(' · ', [
            $of((int) $restaurant->dishes_count, Feature::DishLimit, 'dishes'),
            $of((int) $restaurant->categories_count, Feature::CategoryLimit, 'categories'),
            $of((int) $restaurant->social_links_count, Feature::SocialLinkLimit, 'links'),
        ]);
    }

    /** "320 views, 41 QR scans, 12 orders". */
    private static function trafficSummary(Restaurant $restaurant): string
    {
        $traffic = (new MenuTraffic($restaurant))->totals();
        $orders = $restaurant->orders_count ?? $restaurant->orders()->count();

        return Number::format($traffic['views']).' views, '.Number::format($traffic['scans']).' QR scans, '.Number::format((int) $orders).' orders';
    }

    /** "3 today, 41 in the last 30 days (52,300 tokens, $0.02)", from the usage log. */
    private static function writingHelpSummary(Restaurant $restaurant): string
    {
        $usage = app(WritingHelpLog::class)->of($restaurant);

        return $usage['uses'] === 0
            ? 'Not used in the last '.WritingHelpLog::DAYS.' days'
            : Number::format($usage['today']).' today, '.Number::format($usage['uses']).' in the last '.WritingHelpLog::DAYS.' days ('.Number::format($usage['tokens']).' tokens, '.WritingHelpPrice::format($usage['cost']).')';
    }

    /** "1,240 of 7,500 taken ($0.09 real cost, worth $0.75)", from the 1st. */
    private static function creditsSummary(Restaurant $restaurant): string
    {
        $month = app(WritingHelpCredits::class)->forAdmin($restaurant);
        $cost = WritingHelpPrice::format($month['cost']).' real cost';

        return $month['limit'] === null
            ? Number::format($month['used'])." taken, no cap ({$cost})"
            : Number::format($month['used']).' of '.Number::format($month['limit'])." taken ({$cost}, worth ".WritingHelpPrice::format((float) $month['worth']).')';
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
            PackageStatus::Expired => $restaurant->package?->name.' ended '.$restaurant->package_ends_at->toFormattedDayDateString().'; the menu '.($restaurant->offlineAt()->isPast() ? 'went' : 'goes').' offline '.$restaurant->offlineAt()->toFormattedDayDateString().' until it is extended or changed',
            default => $restaurant->package?->name.($restaurant->package_ends_at === null ? ', forever' : ' until '.$restaurant->package_ends_at->toFormattedDayDateString()),
        };

        return $status.': '.$features;
    }
}
