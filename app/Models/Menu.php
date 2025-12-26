<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Menu extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'description',
        'slug',
        'menu_style',
        'is_active',
        'dish_limit',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'dish_limit' => 'integer',
    ];

    /**
     * Boot the model.
     */
    protected static function boot()
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
    public function settings(): HasOne
    {
        return $this->hasOne(MenuSetting::class);
    }

    /**
     * Get the social links for the menu.
     */
    public function socialLinks(): HasMany
    {
        return $this->hasMany(MenuSocialLink::class)->where('is_active', true)->orderBy('display_order');
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
}
