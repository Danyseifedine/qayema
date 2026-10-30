<?php

namespace App\Models;

use App\Enums\Feature;
use App\Models\Concerns\HasPackage;
use App\Models\Concerns\TakesOrders;
use App\Observers\RestaurantObserver;
use App\Services\Menu\MenuLanguages;
use App\Services\Packages\Entitlements;
use DateTimeZone;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

#[ObservedBy(RestaurantObserver::class)]
class Restaurant extends Model implements HasMedia
{
    /** @use HasFactory<\Database\Factories\RestaurantFactory> */
    use HasFactory, HasPackage, HasTranslations, InteractsWithMedia, TakesOrders;

    /**
     * Slugs that would shadow a real route. The public menu route excludes
     * them, so an owner must never be allowed to pick one.
     *
     * @var string[]
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'livewire', 'storage', 'up', 'sanctum', 'telescope',
        'contact', 'privacy-policy', 'terms-of-service', 'cookie-policy', 'refund-policy',
        'get-started', 'create-account', 'register', 'login', 'logout', 'onboarding', 'auth', 'locale',
        'password', 'forgot-password', 'reset-password', 'temp-upload', 'impersonate',
        // The public pages (App\Support\PortalUrl): Arabic under /ar, and the
        // topic, pricing and guide pages.
        'ar', 'sitemap', 'robots', 'pricing', 'guides', 'qr-menu-lebanon', 'digital-menu-for-cafes',
    ];

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    /**
     * Where spatie's accessor ($restaurant->name, the admin's tables and
     * selects) looks when the language asked for is not written: the
     * menu's main language, the one its name is required in. Anything else
     * falls back to whatever is written (AppServiceProvider). A row read
     * with only some columns (a select's options: id and name) has no main
     * language to hand: English, then whatever is written.
     */
    public function getFallbackLocale(): string
    {
        return MenuLanguages::validMain($this->getAttributes()['main_locale'] ?? null);
    }

    /** What a WhatsApp order can ask the guest for, per way in (whatsappAsks()). */
    public const WHATSAPP_ASKS = [
        'away' => ['name', 'phone', 'address'],
        'table' => ['name', 'phone'],
    ];

    /**
     * Features an owner may switch off on the dashboard's Features page,
     * stored in `switched_off`:
     * - `orders`: guests cannot order (no cart, the endpoint 404s), and the
     *   Orders page leaves the sidebar;
     * - `dine_in`: tables' QR codes only open the menu, guests cannot order
     *   to their table, and the Tables and Table orders pages leave the
     *   sidebar;
     * - `qr`: the QR studio's styling and printable card go; the plain code
     *   and its downloads stay;
     * - `analytics`: the Analytics page leaves the sidebar;
     * - `languages`: the menu is English-only (MenuLanguages::for()).
     * - `variants` / `addons`: a dish's variants or add-ons leave the menu
     *   and orders, and dish saves leave them as they are.
     * Nothing is deleted by switching one off; the package still decides what
     * can be switched on at all.
     *
     * @var array<int, string>
     */
    public const OPTIONAL_FEATURES = ['orders', 'dine_in', 'qr', 'analytics', 'languages', 'variants', 'addons'];

    /** The package flags each optional feature needs one of before it can be on. */
    private const FLAG_OF = [
        'orders' => [Feature::Ordering, Feature::MenuOrdering],
        'dine_in' => [Feature::DineIn, Feature::DineInWhatsApp],
        'qr' => [Feature::QrStudio],
        'analytics' => [Feature::Analytics],
        'languages' => [Feature::MultipleLanguages],
        'variants' => [Feature::Variants],
        'addons' => [Feature::Addons],
    ];

    /** The columns that say which package applies and when. */
    public const PACKAGE_FIELDS = ['package_id', 'package_started_at', 'package_ends_at'];

    /** Columns no guest sees: saving only these leaves the cached menu alone. */
    public const ADMIN_ONLY_FIELDS = ['price_cents', 'pays_with_whish', 'updated_at'];

    /**
     * Why the package is changing, written into the history with the next
     * save. Set by PackageAssigner; never stored on the row.
     */
    public ?string $packageChangeNote = null;

    /** Set while the owner's account deletes this restaurant; never stored. */
    public bool $ownerIsBeingDeleted = false;

    /**
     * What the package put in reach before the package fields changed, read
     * as the save starts (RestaurantObserver); never stored on the row.
     *
     * @var array<int, string>
     */
    public array $inReachBeforeSave = [];

    /**
     * What a new row holds before it is read back: the columns the database
     * fills itself, so a restaurant just created reads them (strict models,
     * AppServiceProvider::keepModelsStrict()).
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'main_locale' => 'en',
        'package_started_at' => null,
        'package_ends_at' => null,
        'price_cents' => null,
        'pays_with_whish' => false,
        'second_currency' => null,
        'exchange_rate' => null,
    ];

    protected $fillable = [
        'user_id',
        'template_id',
        'package_id',
        'package_started_at',
        'package_ends_at',
        'price_cents',
        'pays_with_whish',
        'name',
        'description',
        'slug',
        'google_maps_url',
        'country_code',
        'phone',
        'opening_hours',
        'timezone',
        'currency',
        'second_currency',
        'exchange_rate',
        'main_locale',
        'default_locale',
        'second_locale',
        'is_active',
        'template_settings',
        'qr_settings',
        'switched_off',
        'order_mode',
        'order_types',
        'dine_in_mode',
        'whatsapp_fields',
        'menu_fonts',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            'template_id' => 'integer',
            'package_id' => 'integer',
            'is_active' => 'boolean',
            'opening_hours' => 'array',
            'package_started_at' => 'datetime',
            'package_ends_at' => 'datetime',
            'price_cents' => 'integer',
            'pays_with_whish' => 'boolean',
            'exchange_rate' => 'decimal:12',
            'template_settings' => 'array',
            'qr_settings' => 'array',
            'switched_off' => 'array',
            'order_types' => 'array',
            'whatsapp_fields' => 'array',
            'menu_fonts' => 'array',
        ];
    }

    /** The currency a dish is priced in: the second one when it has it, else the main one. */
    public function currencyOf(Dish $dish): string
    {
        return $dish->currency ?? $this->currency;
    }

    /**
     * What a dish's price turns into the main currency at: the rate when it
     * is priced in the second currency, null when it is in the main one.
     */
    public function rateOf(Dish $dish): ?string
    {
        return $dish->currency !== null && $dish->currency === $this->second_currency && $this->exchange_rate !== null
            ? (string) $this->exchange_rate
            : null;
    }

    /** A dish's currency as stored: null for the main one (validated to be the main or the second). */
    public function ownCurrency(?string $currency): ?string
    {
        return $currency === null || $currency === $this->currency ? null : $currency;
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return HasMany<Template, $this> the designs made for this restaurant alone */
    public function ownDesigns(): HasMany
    {
        return $this->hasMany(Template::class);
    }

    /** @return BelongsTo<Package, $this> */
    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    /** @return HasMany<Category, $this> */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('display_order');
    }

    /** @return HasMany<Dish, $this> */
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class)->orderBy('display_order');
    }

    /** @return HasMany<RestaurantSocialLink, $this> */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(RestaurantSocialLink::class);
    }

    /** @return HasMany<Offer, $this> */
    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class)->orderBy('display_order')->orderBy('id');
    }

    /** @return HasMany<MenuSession, $this> */
    public function menuSessions(): HasMany
    {
        return $this->hasMany(MenuSession::class);
    }

    /** @return HasMany<MenuEvent, $this> */
    public function menuEvents(): HasMany
    {
        return $this->hasMany(MenuEvent::class);
    }

    /**
     * The analytics of each past day, added up from the raw rows (MenuDays).
     *
     * @return HasMany<MenuDay, $this>
     */
    public function menuDays(): HasMany
    {
        return $this->hasMany(MenuDay::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    /**
     * The tables, each with its own QR code, in the owner's order.
     *
     * @return HasMany<DiningTable, $this>
     */
    public function diningTables(): HasMany
    {
        return $this->hasMany(DiningTable::class)->orderBy('sort_order')->orderBy('id');
    }

    /** @return HasOne<PosConnection, $this> */
    public function posConnection(): HasOne
    {
        return $this->hasOne(PosConnection::class);
    }

    /** @return HasMany<FeatureGrant, $this> */
    public function featureGrants(): HasMany
    {
        return $this->hasMany(FeatureGrant::class);
    }

    /**
     * The grants in force, for a list to load with its page: Entitlements
     * reads them from here when loaded, rather than once per row.
     *
     * @return HasMany<FeatureGrant, $this>
     */
    public function activeFeatureGrants(): HasMany
    {
        return $this->featureGrants()->active();
    }

    /** @return HasMany<PackageChange, $this> */
    public function packageChanges(): HasMany
    {
        return $this->hasMany(PackageChange::class)->latest('created_at')->latest('id');
    }

    /**
     * The main language, then the second one when there is one.
     *
     * @return array<int, string>
     */
    public function menuLanguages(): array
    {
        return MenuLanguages::for($this);
    }

    /**
     * The optional features this owner switched off.
     *
     * @return array<int, string>
     */
    public function switchedOff(): array
    {
        return array_values(array_intersect(self::OPTIONAL_FEATURES, (array) $this->switched_off));
    }

    public function isSwitchedOff(string $feature): bool
    {
        return in_array($feature, $this->switchedOff(), true);
    }

    /**
     * The optional features the package and grants include, whether or not
     * the owner switched them off.
     *
     * @return array<int, string>
     */
    public function featuresInReach(): array
    {
        $entitlements = $this->entitlements();

        return array_keys(array_filter(
            self::FLAG_OF,
            fn (array $flags): bool => collect($flags)->contains(fn (Feature $flag): bool => $entitlements->can($flag)),
        ));
    }

    /**
     * A feature a new package or grant brings arrives switched on: the owner
     * paid for it, so a choice made while it was out of reach no longer
     * holds. Features that were already in reach keep the owner's choice.
     *
     * @param  array<int, string>  $inReachBefore  featuresInReach() before the change
     */
    public function switchOnWhatCameIntoReach(array $inReachBefore): void
    {
        $cameIntoReach = array_diff($this->featuresInReach(), $inReachBefore);
        $off = array_values(array_diff($this->switchedOff(), $cameIntoReach));

        if ($off !== $this->switchedOff()) {
            $this->forceFill(['switched_off' => $off])->saveQuietly();
        }
    }

    /** The QR studio's styling is on: in the package and not switched off. */
    public function hasQrStudio(): bool
    {
        return $this->entitlements()->can(Feature::QrStudio) && ! $this->isSwitchedOff('qr');
    }

    /** The menu shows its second language: in the package and not switched off. */
    public function showsSecondLanguage(): bool
    {
        return $this->entitlements()->can(Feature::MultipleLanguages) && ! $this->isSwitchedOff('languages');
    }

    /** Dishes show their variants (size, spice level): in the package and not switched off. */
    public function showsVariants(): bool
    {
        return $this->entitlements()->can(Feature::Variants) && ! $this->isSwitchedOff('variants');
    }

    /** Dishes show their add-ons (extra cheese): in the package and not switched off. */
    public function showsAddons(): bool
    {
        return $this->entitlements()->can(Feature::Addons) && ! $this->isSwitchedOff('addons');
    }

    /**
     * Dishes show their "build your own" (half and half pizzas): only where
     * an admin switched it on for this restaurant, since no package has it,
     * and not while the menu is connected to a POS.
     */
    public function showsBuilds(): bool
    {
        return $this->entitlements()->can(Feature::BuildYourOwn) && ! $this->isConnectedToPos();
    }

    /**
     * Offers reach the menu and its prices: in the package, and off while
     * the menu is connected to a POS (saved offers wait for the disconnect).
     */
    public function showsOffers(): bool
    {
        return $this->entitlements()->can(Feature::Offers) && ! $this->isConnectedToPos();
    }

    /**
     * The menu's categories and dishes come from a POS branch: linked, and
     * Connect to POS still on (an admin switches it on; switched off, the
     * link sleeps and the owner edits the menu again). While true, Offers
     * and Build your own are off and the POS owns what it sends.
     */
    public function isConnectedToPos(): bool
    {
        if (! $this->entitlements()->can(Feature::PosConnection)) {
            return false;
        }

        // Read once and kept: the menu and its prices ask many times a page.
        if (! $this->relationLoaded('posConnection')) {
            $this->setRelation('posConnection', $this->posConnection()->first());
        }

        return $this->posConnection !== null;
    }

    /** The owner's colours, fonts and other design choices reach the menu. */
    public function hasAppearance(): bool
    {
        return $this->entitlements()->can(Feature::Appearance);
    }

    /** Whether this restaurant's package lets it use a design. */
    public function mayUseTemplate(Template $template): bool
    {
        // Made for one restaurant: that one only, whatever its package.
        if ($template->restaurant_id !== null) {
            return $template->restaurant_id === $this->id;
        }

        return ! $template->is_premium || $this->entitlements()->can(Feature::PremiumDesigns);
    }

    /**
     * The design the menu is drawn in: the owner's choice while their package
     * allows it, else the fallback design. The choice itself is never
     * overwritten, so it comes back with the package.
     */
    public function menuTemplate(): ?Template
    {
        $template = $this->template;

        if ($template === null || $this->mayUseTemplate($template)) {
            return $template;
        }

        return Template::fallback();
    }

    /**
     * A design's settings as the menu draws them: the owner's choices for
     * that design over its defaults, or only the defaults when the package has
     * no Appearance, with the choices kept for when it does. The design the
     * menu is drawn in when none is given.
     *
     * @return array<string, mixed>
     */
    public function designSettings(?Template $template = null): array
    {
        $template ??= $this->menuTemplate();

        if ($template === null) {
            return [];
        }

        return $template->resolveSettings($this->hasAppearance() ? $this->chosenDesignSettings($template) : []);
    }

    /**
     * Only what the owner changed on one design.
     *
     * @return array<string, mixed>
     */
    public function chosenDesignSettings(Template $template): array
    {
        return (array) ($this->designSettingsByTemplate()[$template->id] ?? []);
    }

    /**
     * Carry how the menu looks into another design: every setting the two
     * share (the colours, the name beside the logo) takes the value the owner
     * sees now, so switching designs changes the layout, never the look. A
     * setting only the new design has keeps its own; a value it would not
     * take (a removed choice) is left out.
     */
    public function carryDesignSettingsTo(Template $target): void
    {
        $current = $this->menuTemplate();

        if ($current === null || $current->is($target)) {
            return;
        }

        $seen = $current->resolveSettings($this->chosenDesignSettings($current));
        $shared = array_intersect_key($seen, $target->defaultSettings());
        $taken = $target->resolveSettings($shared);
        $carried = array_filter($shared, fn (mixed $value, string $key): bool => $taken[$key] === $value, ARRAY_FILTER_USE_BOTH);

        if ($carried !== []) {
            $this->saveDesignSettings($target, $carried);
        }
    }

    /**
     * Save some of a design's colours. Only that design's entry changes; a
     * null value drops the owner's choice, so the design default applies.
     *
     * @param  array<string, mixed>  $values
     */
    public function saveDesignSettings(Template $template, array $values): void
    {
        $chosen = array_filter(
            array_intersect_key(
                array_replace($this->chosenDesignSettings($template), $values),
                $template->defaultSettings(),
            ),
            fn ($value): bool => $value !== null,
        );

        $all = $this->designSettingsByTemplate();
        // Assigned by key, never merged: array_merge renumbers integer keys
        // and would hand one design's colours to another.
        $all[$template->id] = $chosen;

        $this->update(['template_settings' => array_filter($all, fn (array $entry): bool => $entry !== [])]);
    }

    /**
     * The column as {template_id: values}.
     *
     * @return array<int, array<string, mixed>>
     */
    private function designSettingsByTemplate(): array
    {
        return (array) $this->template_settings;
    }

    public function entitlements(): Entitlements
    {
        return Entitlements::for($this);
    }

    /** The menu's public address on APP_URL, e.g. https://qayema.com/olive-house. */
    public function publicUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$this->slug;
    }

    /** The restaurant's timezone: the app's when unset or not a real one. */
    public function localTimezone(): string
    {
        return self::validTimezone($this->timezone);
    }

    /**
     * A stored `timezone` as localTimezone() reads it: kept when PHP knows
     * it, older names included (`Asia/Calcutta`, which a browser may still
     * send and UpdateRestaurantRequest accepts); else the app's; else UTC.
     */
    public static function validTimezone(?string $timezone): string
    {
        if (self::isTimezone($timezone)) {
            return $timezone;
        }

        $fallback = config('app.timezone');

        return self::isTimezone($fallback) ? $fallback : 'UTC';
    }

    /** Whether PHP knows this timezone, older names included. */
    public static function isTimezone(mixed $timezone): bool
    {
        $known = once(fn (): array => array_flip(timezone_identifiers_list(DateTimeZone::ALL_WITH_BC)));

        return is_string($timezone) && isset($known[$timezone]);
    }

    /**
     * Where a new restaurant most likely is: the owner's browser's timezone,
     * else its country's first one (Lebanon has only Asia/Beirut), else none.
     */
    public static function likelyTimezone(?string $browser, ?string $countryCode): ?string
    {
        if (self::isTimezone($browser)) {
            return $browser;
        }

        if ($countryCode === null || ! preg_match('/^[A-Za-z]{2}$/', $countryCode)) {
            return null;
        }

        return DateTimeZone::listIdentifiers(DateTimeZone::PER_COUNTRY, strtoupper($countryCode))[0] ?? null;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('cover_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * The cover at phone size: the first picture a guest sees, so a phone
     * gets a third of the 1920px file. Made with the upload; a cover stored
     * before this falls back to the full one until regenerated.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('phone')
            ->performOnCollections('cover_image')
            ->width(960)
            ->format('webp')
            ->quality(78)
            ->nonQueued();
    }
}
