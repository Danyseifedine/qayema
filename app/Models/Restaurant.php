<?php

namespace App\Models;

use App\Services\Global\Entitlements;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;

class Restaurant extends Model implements HasMedia
{
    use HasFactory, HasTranslations, InteractsWithMedia;

    /** @var string[] */
    public array $translatable = ['name', 'description', 'address'];

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'slug',
        'country_code',
        'phone',
        'is_active',
        'template_id',
        'template_settings',
        'qr_settings',
        'address',
        'google_maps_url',
        'currency',
        'default_locale',
        'timezone',
        'restaurant_type_id',
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

        static::saved(function (self $restaurant) {
            if ($restaurant->wasChanged('template_id')) {
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

    public function restaurantType(): BelongsTo
    {
        return $this->belongsTo(RestaurantType::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
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

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function featureGrants(): HasMany
    {
        return $this->hasMany(RestaurantFeature::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function entitlements(): Entitlements
    {
        return Entitlements::for($this);
    }

    public function activeTemplateSubscription(): ?Subscription
    {
        if ($this->template_id === null) {
            return null;
        }

        return $this->subscriptions()
            ->active()
            ->where('subscribable_type', Template::class)
            ->where('subscribable_id', $this->template_id)
            ->latest('current_period_end')
            ->first();
    }

    /**
     * The public menu is served only when the assigned template is free or
     * carries an active (or in-grace) subscription. Expiry takes the menu
     * offline without touching any data.
     */
    public function menuIsPubliclyAvailable(): bool
    {
        if (! $this->template || $this->template->isFree()) {
            return true;
        }

        return $this->activeTemplateSubscription() !== null;
    }

    public function getDishLimitAttribute(): int
    {
        return $this->entitlements()->limit('dish_limit');
    }

    public function getCategoryLimitAttribute(): int
    {
        return $this->entitlements()->limit('category_limit');
    }

    public function getSocialLinkLimitAttribute(): int
    {
        return $this->entitlements()->limit('social_link_limit');
    }

    public function hasReachedDishLimit(): bool
    {
        return $this->dishes()->count() >= $this->dish_limit;
    }

    public function getRemainingDishSlots(): int
    {
        return max(0, $this->dish_limit - $this->dishes()->count());
    }

    public function hasReachedCategoryLimit(): bool
    {
        return $this->categories()->count() >= $this->category_limit;
    }

    public function getRemainingCategorySlots(): int
    {
        return max(0, $this->category_limit - $this->categories()->count());
    }

    public function hasReachedSocialLinkLimit(): bool
    {
        return $this->socialLinks()->count() >= $this->social_link_limit;
    }

    public function getRemainingSocialLinkSlots(): int
    {
        return max(0, $this->social_link_limit - $this->socialLinks()->count());
    }

    public function getTotalViews(): int
    {
        return $this->statistics()->count();
    }

    public function getUniqueVisitors(): int
    {
        return $this->statistics()->distinct('session_id')->count('session_id');
    }

    public function getAverageTimeSpent(): float
    {
        $avg = $this->statistics()
            ->whereNotNull('time_spent')
            ->where('time_spent', '>', 0)
            ->avg('time_spent');

        return $avg ? (float) $avg : 0.0;
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

    public function getTotalTimeSpent(): int
    {
        return $this->statistics()
            ->whereNotNull('time_spent')
            ->sum('time_spent') ?? 0;
    }

    public function getWhatsAppOrderCount(string $period = 'all'): int
    {
        $query = $this->statistics()->where('whatsapp_orders', '>', 0);

        return match ($period) {
            'today' => $query->whereDate('viewed_at', today())->sum('whatsapp_orders'),
            'week' => $query->whereBetween('viewed_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('whatsapp_orders'),
            'month' => $query->whereMonth('viewed_at', now()->month)->whereYear('viewed_at', now()->year)->sum('whatsapp_orders'),
            default => $query->sum('whatsapp_orders'),
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
