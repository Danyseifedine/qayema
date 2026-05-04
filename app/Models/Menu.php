<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Menu extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'slug',
        'is_active',
        'dish_limit',
        'category_limit',
        'social_link_limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dish_limit' => 'integer',
        'category_limit' => 'integer',
        'social_link_limit' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($menu) {
            if (empty($menu->slug)) {
                $menu->slug = Str::slug($menu->name);
            }
        });
    }

    /**
     * Get the user that owns the menu.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the categories for the menu.
     */
    public function categories(): HasMany
    {
        return $this->hasMany(Category::class)->orderBy('display_order');
    }

    /**
     * Get the dishes for the menu.
     */
    public function dishes(): HasMany
    {
        return $this->hasMany(Dish::class)->orderBy('display_order');
    }

    /**
     * Get the settings for the menu.
     */
    public function settings(): HasMany
    {
        return $this->hasMany(MenuSetting::class);
    }

    /**
     * Get the social links for the menu.
     */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(MenuSocialLink::class);
    }

    /**
     * Get the statistics for the menu.
     */
    public function statistics(): HasMany
    {
        return $this->hasMany(MenuStatistic::class);
    }

    /**
     * Check if menu has reached dish limit.
     */
    public function hasReachedDishLimit(): bool
    {
        return $this->dishes()->count() >= $this->dish_limit;
    }

    /**
     * Get remaining dish slots.
     */
    public function getRemainingDishSlots(): int
    {
        return max(0, $this->dish_limit - $this->dishes()->count());
    }

    /**
     * Check if menu has reached category limit.
     */
    public function hasReachedCategoryLimit(): bool
    {
        return $this->categories()->count() >= $this->category_limit;
    }

    /**
     * Get remaining category slots.
     */
    public function getRemainingCategorySlots(): int
    {
        return max(0, $this->category_limit - $this->categories()->count());
    }

    /**
     * Check if menu has reached social link limit.
     */
    public function hasReachedSocialLinkLimit(): bool
    {
        return $this->socialLinks()->count() >= $this->social_link_limit;
    }

    /**
     * Get remaining social link slots.
     */
    public function getRemainingSocialLinkSlots(): int
    {
        return max(0, $this->social_link_limit - $this->socialLinks()->count());
    }

    /**
     * Get total views count.
     */
    public function getTotalViews(): int
    {
        return $this->statistics()->count();
    }

    /**
     * Get unique visitors count.
     */
    public function getUniqueVisitors(): int
    {
        return $this->statistics()->distinct('session_id')->count('session_id');
    }

    /**
     * Get average time spent.
     */
    public function getAverageTimeSpent(): float
    {
        $avg = $this->statistics()
            ->whereNotNull('time_spent')
            ->where('time_spent', '>', 0)
            ->avg('time_spent');

        return $avg ? (float) $avg : 0.0;
    }

    /**
     * Get total time spent.
     */
    public function getTotalTimeSpent(): int
    {
        return $this->statistics()
            ->whereNotNull('time_spent')
            ->sum('time_spent') ?? 0;
    }

    /**
     * Set default settings for the menu.
     */
    public function setDefaultSettings(): void
    {
        if ($this->settings()->exists()) {
            return;
        }

        $defaults = config('menu_settings.defaults', []);

        foreach ($defaults as $key => $value) {
            $setting = Setting::where('key', $key)->first();

            if ($setting) {
                MenuSetting::updateOrCreate(
                    [
                        'menu_id' => $this->id,
                        'setting_id' => $setting->id,
                    ],
                    [
                        'value' => $value,
                    ]
                );
            }
        }
    }

    /**
     * Get all menu settings as an associative array.
     */
    public function getSettings(): array
    {
        $result = config('menu_settings.defaults', []);
        $settings = $this->settings()->with('setting')->get();

        foreach ($settings as $menuSetting) {
            if ($menuSetting->setting) {
                $result[$menuSetting->setting->key] = $menuSetting->value;
            }
        }

        return $result;
    }

    /**
     * Get a specific menu setting value.
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        $settings = $this->getSettings();

        return $settings[$key] ?? $default;
    }
}
