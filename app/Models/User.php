<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Lab404\Impersonate\Models\Impersonate;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Impersonate, Notifiable, SoftDeletes;

    public function canImpersonate(): bool
    {
        return $this->isAdmin();
    }

    public function canBeImpersonated(): bool
    {
        return $this->isMenuOwner();
    }

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'onboarding_step',
        'onboarding_completed_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'onboarding_step' => 'integer',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isMenuOwner(): bool
    {
        return $this->role === UserRole::MenuOwner;
    }

    public function isRestaurantSetupComplete(): bool
    {
        return $this->restaurant !== null;
    }

    public function isProfileComplete(): bool
    {
        return $this->isRestaurantSetupComplete();
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    public function currentOnboardingStep(): int
    {
        return min(($this->onboarding_step ?? 0) + 1, 5);
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->isAdmin();
    }
}
