<?php

namespace App\Models;

use App\Enums\Feature;
use App\Enums\PackageStatus;
use App\Services\Menu\MenuLanguages;
use App\Services\Packages\Entitlements;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Restaurant extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    /**
     * Slugs that would shadow a real route. The public menu route excludes
     * them, so an owner must never be allowed to pick one.
     *
     * @var string[]
     */
    public const RESERVED_SLUGS = [
        'admin', 'api', 'livewire', 'storage', 'up', 'sanctum', 'telescope',
        'contact', 'privacy-policy', 'terms-of-service', 'cookie-policy', 'refund-policy',
        'get-started', 'register', 'login', 'logout', 'onboarding', 'auth', 'locale',
        'password', 'forgot-password', 'reset-password', 'temp-upload', 'impersonate',
    ];

    /** @var string[] */
    public array $translatable = ['name', 'description'];

    /**
     * Features an owner may switch off on the dashboard's Features page,
     * stored in `switched_off`:
     * - `orders`: guests cannot order (no cart, the endpoint 404s), and the
     *   Orders page leaves the sidebar;
     * - `qr`: the QR studio's styling and printable card go; the plain code
     *   and its downloads stay;
     * - `analytics`: the Analytics page leaves the sidebar;
     * - `languages`: the menu is English-only (MenuLanguages::for()).
     * Nothing is deleted by switching one off; the package still decides what
     * can be switched on at all.
     *
     * @var array<int, string>
     */
    public const OPTIONAL_FEATURES = ['orders', 'qr', 'analytics', 'languages'];

    /** The package flag each optional feature needs before it can be on. */
    private const FLAG_OF = [
        'orders' => Feature::Ordering,
        'qr' => Feature::QrStudio,
        'analytics' => Feature::Analytics,
        'languages' => Feature::MultipleLanguages,
    ];

    /** The columns that say which package applies and when. */
    public const PACKAGE_FIELDS = ['package_id', 'package_started_at', 'package_ends_at'];

    /**
     * Why the package is changing, written into the history with the next
     * save. Set by PackageAssigner; never stored on the row.
     */
    public ?string $packageChangeNote = null;

    /**
     * What the package put in reach before the package fields changed, read
     * as the save starts; never stored on the row.
     *
     * @var array<int, string>
     */
    private array $inReachBeforeSave = [];

    protected $fillable = [
        'user_id',
        'template_id',
        'package_id',
        'package_started_at',
        'package_ends_at',
        'name',
        'description',
        'slug',
        'google_maps_url',
        'country_code',
        'phone',
        'opening_hours',
        'timezone',
        'currency',
        'default_locale',
        'second_locale',
        'is_active',
        'template_settings',
        'qr_settings',
        'switched_off',
        'menu_fonts',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
            
            'package_id' => 'integer',
            'is_active' => 'boolean',
            'opening_hours' => 'array',
            'package_started_at' => 'datetime',
            'package_ends_at' => 'datetime',
            'template_settings' => 'array',
            'qr_settings' => 'array',
            'switched_off' => 'array',
            'menu_fonts' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $restaurant) {
            // Every restaurant is on a package from the moment it exists, so
            // limits never have to cope with "no package yet".
            $restaurant->package_id ??= Package::default()?->id;
            $restaurant->package_started_at ??= now();

            if (empty($restaurant->slug)) {
                $base = Str::slug($restaurant->name);
                $slug = $base !== '' ? $base : 'menu';
                $count = 2;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.$count;
                    $count++;
                }
                $restaurant->slug = $slug;
            }
        });

        // Every write to the package fields lands in the history, whichever
        // path made it (PackageAssigner, the admin form, a console command).
        static::created(function (self $restaurant): void {
            $restaurant->recordPackageChange(null);
        });

        static::updating(function (self $restaurant): void {
            if ($restaurant->isDirty(self::PACKAGE_FIELDS)) {
                // The row as it still is in the database, before this save.
                $restaurant->inReachBeforeSave = $restaurant->newFromBuilder($restaurant->getRawOriginal())->featuresInReach();
            }
        });

        static::saved(function (self $restaurant): void {
            // Only an update carries changes; the insert is recorded above.
            if (! $restaurant->wasChanged(self::PACKAGE_FIELDS)) {
                return;
            }

            Entitlements::flush($restaurant->id);
            $restaurant->recordPackageChange($restaurant->getOriginal('package_id'));
            // The loaded package may be the one it just left.
            $restaurant->unsetRelation('package');
            $restaurant->switchOnWhatCameIntoReach($restaurant->inReachBeforeSave);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('display_order');
    }

    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class)->orderBy('display_order');
    }

    public function socialLinks(): HasMany
    {
        return $this->hasMany(RestaurantSocialLink::class);
    }

    public function menuSessions(): HasMany
    {
        return $this->hasMany(MenuSession::class);
    }

    public function menuEvents(): HasMany
    {
        return $this->hasMany(MenuEvent::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('placed_at');
    }

    public function featureGrants(): HasMany
    {
        return $this->hasMany(FeatureGrant::class);
    }

    /** @return HasMany<PackageChange, $this> */
    public function packageChanges(): HasMany
    {
        return $this->hasMany(PackageChange::class)->latest('created_at')->latest('id');
    }

    /**
     * Restaurants whose assigned package is in force now.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageActive(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $q) => $q->whereNull('package_started_at')->orWhere('package_started_at', '<=', now()))
            ->where(fn (Builder $q) => $q->whereNull('package_ends_at')->orWhere('package_ends_at', '>', now()));
    }

    /**
     * Restaurants whose assigned package has a start still to come.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageScheduled(Builder $query): Builder
    {
        return $query->where('package_started_at', '>', now());
    }

    /**
     * Restaurants whose assigned package has ended.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageExpired(Builder $query): Builder
    {
        return $query->where('package_ends_at', '<=', now());
    }

    /**
     * Restaurants whose package is in force and ends within the given days.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopePackageEndingWithin(Builder $query, int $days): Builder
    {
        return $query->packageActive()->whereBetween('package_ends_at', [now(), now()->addDays($days)]);
    }

    /**
     * Restaurants on a package right now, whether assigned and in force or
     * fallen back to it because their own is not.
     *
     * @param  Builder<Restaurant>  $query
     * @return Builder<Restaurant>
     */
    public function scopeOnPackage(Builder $query, int $packageId): Builder
    {
        return $query->where(function (Builder $q) use ($packageId): void {
            $q->where(fn (Builder $assigned) => $assigned->where('package_id', $packageId)->packageActive());

            if ($packageId === Package::default()?->id) {
                $q->orWhere(fn (Builder $fallen) => $fallen->whereNot(fn (Builder $active) => $active->packageActive()))
                    ->orWhereNull('package_id');
            }
        });
    }

    /** Where the assigned package stands between its start and end dates. */
    public function packageStatus(): PackageStatus
    {
        return match (true) {
            $this->package_started_at !== null && $this->package_started_at->isFuture() => PackageStatus::Scheduled,
            $this->package_ends_at !== null && $this->package_ends_at->isPast() => PackageStatus::Expired,
            default => PackageStatus::Active,
        };
    }

    /** True once an admin-set expiry has passed. */
    public function packageExpired(): bool
    {
        return $this->packageStatus() === PackageStatus::Expired;
    }

    /**
     * The package the limits actually come from: the assigned one while it
     * is in force, else the default. The assignment is left alone so an admin
     * can still see what is coming or what lapsed.
     */
    public function effectivePackage(): ?Package
    {
        if ($this->package_id !== null && $this->packageStatus() === PackageStatus::Active) {
            return $this->package;
        }

        return Package::default();
    }

    private function recordPackageChange(?int $fromPackageId): void
    {
        $this->packageChanges()->create([
            'from_package_id' => $fromPackageId,
            'to_package_id' => $this->package_id,
            'starts_at' => $this->package_started_at,
            'ends_at' => $this->package_ends_at,
            'changed_by' => auth()->id(),
            'note' => $this->packageChangeNote,
        ]);

        $this->packageChangeNote = null;
    }

    /**
     * English, then the second language when there is one.
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

        return array_keys(array_filter(self::FLAG_OF, fn (Feature $flag): bool => $entitlements->can($flag)));
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

    /** Guests can order: the package includes it and the owner has not switched it off. */
    public function takesOrders(): bool
    {
        return $this->entitlements()->can(Feature::Ordering) && ! $this->isSwitchedOff('orders');
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

    /** The owner's colours, fonts and other design choices reach the menu. */
    public function hasAppearance(): bool
    {
        return $this->entitlements()->can(Feature::Appearance);
    }

    /** Whether this restaurant's package lets it use a design. */
    public function mayUseTemplate(Template $template): bool
    {
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

    /**
     * The link every QR code for this menu encodes. `?qr=1` is how a visit is
     * counted as a scan, so the printed card, the dashboard and the menu's own
     * pop-up all use this one.
     */
    public function qrUrl(): string
    {
        return rtrim((string) config('app.url'), '/').'/'.$this->slug.'?qr=1';
    }

    /**
     * The QR card's default design.
     *
     * @return array<string, mixed>
     */
    public function qrDefaultDesign(): array
    {
        // The simple QR: black on white, square everything, no logo.
        return [
            'dot_style' => 'square',
            'dot_color' => '#000000',
            'dot_gradient' => null,
            'gradient_type' => 'linear',
            'corner_style' => 'square',
            'corner_color' => '#000000',
            'eye_style' => 'square',
            'eye_color' => '#000000',
            'background' => '#FFFFFF',
            'logo' => false,
            'logo_size' => 'medium',
            'card_theme' => 'light',
            'title' => $this->name,
            'subtitle' => null,
            'cta' => null,
            'show_url' => true,
        ];
    }

    /**
     * The saved QR design merged over the defaults, shared by the dashboard
     * studio and the public card page so both render the exact same card.
     *
     * @return array<string, mixed>
     */
    public function qrDesign(): array
    {
        $defaults = $this->qrDefaultDesign();

        // Only keys the current design knows, so a field that is renamed or
        // dropped later never lingers in what the card is drawn from.
        $saved = array_intersect_key(
            array_filter((array) $this->qr_settings, fn ($value) => $value !== null),
            $defaults,
        );

        return array_merge($defaults, $saved);
    }

    /** Null means unlimited on this package. */
    public function getDishLimitAttribute(): ?int
    {
        return $this->entitlements()->limit(Feature::DishLimit);
    }

    public function getCategoryLimitAttribute(): ?int
    {
        return $this->entitlements()->limit(Feature::CategoryLimit);
    }

    public function getSocialLinkLimitAttribute(): ?int
    {
        return $this->entitlements()->limit(Feature::SocialLinkLimit);
    }

    public function hasReachedDishLimit(): bool
    {
        return $this->dish_limit !== null && $this->dishes()->count() >= $this->dish_limit;
    }

    public function hasReachedCategoryLimit(): bool
    {
        return $this->category_limit !== null && $this->categories()->count() >= $this->category_limit;
    }

    public function hasReachedSocialLinkLimit(): bool
    {
        return $this->social_link_limit !== null && $this->socialLinks()->count() >= $this->social_link_limit;
    }

    public function getTotalViews(): int
    {
        return $this->menuSessions()->count();
    }

    public function getQrScanCount(string $period = 'all'): int
    {
        $query = $this->menuSessions()->where('via_qr', true);

        return match ($period) {
            'today' => $query->whereDate('viewed_at', today())->count(),
            'week' => $query->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'month' => $query->whereMonth('viewed_at', now()->month)->whereYear('viewed_at', now()->year)->count(),
            default => $query->count(),
        };
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
}
