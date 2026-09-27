<?php

namespace App\Models;

use App\Enums\Feature;
use App\Services\Global\Entitlements;
use App\Services\Global\MenuLanguages;
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
     * Dashboard sections an owner may switch off. The core ones — overview,
     * menu, templates, restaurant, package, profile — always stay.
     *
     * @var array<int, string>
     */
    public const HIDEABLE_SECTIONS = ['analytics', 'orders', 'qr', 'social-links'];

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
        'hidden_sections',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'opening_hours' => 'array',
            'package_started_at' => 'datetime',
            'package_ends_at' => 'datetime',
            'template_settings' => 'array',
            'qr_settings' => 'array',
            'hidden_sections' => 'array',
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

        static::saved(function (self $restaurant): void {
            if ($restaurant->wasChanged(['package_id', 'package_ends_at'])) {
                Entitlements::flush($restaurant->id);
            }
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

    public function statistics(): HasMany
    {
        return $this->hasMany(RestaurantStatistic::class);
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
        return $this->hasMany(RestaurantFeature::class);
    }

    /** True once an admin-set expiry has passed. */
    public function packageExpired(): bool
    {
        return $this->package_ends_at !== null && $this->package_ends_at->isPast();
    }

    /**
     * The package the limits actually come from: the assigned one while it
     * lasts, then the default. The assignment is left alone so an admin can
     * still see what expired.
     */
    public function effectivePackage(): ?Package
    {
        if ($this->package_id !== null && ! $this->packageExpired()) {
            return $this->package;
        }

        return Package::default();
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
     * The sections this owner switched off, limited to ones that can be.
     *
     * @return array<int, string>
     */
    public function hiddenSections(): array
    {
        return array_values(array_intersect(self::HIDEABLE_SECTIONS, (array) $this->hidden_sections));
    }

    public function entitlements(): Entitlements
    {
        return Entitlements::for($this);
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
        return $this->statistics()->count();
    }

    public function getQrScanCount(string $period = 'all'): int
    {
        $query = $this->statistics()->where('via_qr', true);

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
