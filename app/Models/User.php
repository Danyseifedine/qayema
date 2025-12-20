<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Authenticatable implements HasMedia
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, InteractsWithMedia, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'restaurant_name',
        'phone',
        'address',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Get the menus for the user.
     */
    public function menus()
    {
        return $this->hasMany(Menu::class);
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if user is menu owner.
     */
    public function isMenuOwner(): bool
    {
        return $this->role === 'menu_owner';
    }

    /**
     * Check if step 1 (restaurant info) is complete.
     */
    public function isStep1Complete(): bool
    {
        return ! empty($this->restaurant_name) &&
               ! empty($this->phone) &&
               ! empty($this->address);
    }

    /**
     * Check if step 2 (images) is complete.
     */
    public function isStep2Complete(): bool
    {
        return $this->hasMedia('logo') && $this->hasMedia('cover_image');
    }

    /**
     * Check if restaurant setup is complete.
     */
    public function isRestaurantSetupComplete(): bool
    {
        return $this->isStep1Complete() && $this->isStep2Complete();
    }

    /**
     * Check if profile is complete (for backward compatibility).
     */
    public function isProfileComplete(): bool
    {
        return $this->isRestaurantSetupComplete();
    }

    /**
     * Register media collections.
     */
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
