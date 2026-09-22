<?php

namespace App\Models;

use App\Enums\Feature;
use App\Services\Global\Package;
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
        'admin', 'api', 'livewire', 'storage', 'up', 'paddle', 'sanctum', 'telescope',
        'contact', 'privacy-policy', 'terms-of-service', 'cookie-policy', 'refund-policy',
        'get-started', 'register', 'login', 'logout', 'onboarding', 'auth', 'locale',
        'password', 'forgot-password', 'reset-password', 'temp-upload', 'impersonate',
    ];

    /** @var string[] */
    public array $translatable = ['name', 'description', 'address'];

    protected $fillable = [
        'user_id',
        'template_id',
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
            'template_settings' => 'array',
            'qr_settings' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $restaurant) {
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
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
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

    public function templatePurchases(): HasMany
    {
        return $this->hasMany(TemplatePurchase::class);
    }

    public function package(): Package
    {
        return Package::for($this);
    }

    /**
     * Whether this restaurant may use a template: free ones are open to
     * everyone, paid ones only once bought.
     */
    public function owns(Template $template): bool
    {
        if ($template->isFree()) {
            return true;
        }

        // Answer from the loaded relation when the caller eager-loaded it, so
        // listing every template stays a single query.
        if ($this->relationLoaded('templatePurchases')) {
            return $this->templatePurchases->contains('template_id', $template->id);
        }

        return $this->templatePurchases()->where('template_id', $template->id)->exists();
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

    public function getDishLimitAttribute(): int
    {
        return $this->package()->limit(Feature::DishLimit);
    }

    public function getCategoryLimitAttribute(): int
    {
        return $this->package()->limit(Feature::CategoryLimit);
    }

    public function getSocialLinkLimitAttribute(): int
    {
        return $this->package()->limit(Feature::SocialLinkLimit);
    }

    public function hasReachedDishLimit(): bool
    {
        return $this->dishes()->count() >= $this->dish_limit;
    }

    public function hasReachedCategoryLimit(): bool
    {
        return $this->categories()->count() >= $this->category_limit;
    }

    public function hasReachedSocialLinkLimit(): bool
    {
        return $this->socialLinks()->count() >= $this->social_link_limit;
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
