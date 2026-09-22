<?php

namespace App\Models;

use App\Enums\Feature;
use App\Services\Global\Entitlements;
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
    public array $translatable = ['name', 'description', 'address'];

    protected $fillable = [
        'user_id',
        'template_id',
        'package_id',
        'package_started_at',
        'package_ends_at',
        'name',
        'description',
        'slug',
        'address',
        'google_maps_url',
        'country_code',
        'phone',
        'currency',
        'default_locale',
        'is_active',
        'template_settings',
        'qr_settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'package_started_at' => 'datetime',
            'package_ends_at' => 'datetime',
            'template_settings' => 'array',
            'qr_settings' => 'array',
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
        return [
            'bg' => 'cream',
            'dot' => '#15120a',
            'eye' => '#15120a',
            'dot_style' => 'square',
            'corner' => 'round',
            'logo' => 'none',
            'show_url' => true,
            'name' => $this->name,
            'tagline' => null,
            'cta' => null,
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
        $saved = array_filter((array) $this->qr_settings, fn ($value) => $value !== null);

        return array_merge($this->qrDefaultDesign(), $saved);
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
