<?php

namespace App\Models;

use App\Enums\UserRole;
use Filament\Models\Contracts\FilamentUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Lab404\Impersonate\Models\Impersonate;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** How many steps the onboarding wizard has. */
    public const ONBOARDING_STEPS = 3;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Impersonate, Notifiable;

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
        'username',
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
            'password' => 'hashed',
            'role' => UserRole::class,
            'onboarding_step' => 'integer',
            'onboarding_completed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // The database would delete the restaurant on its own, but without
        // its images: going through the model lets the media library remove
        // the logo, cover and every dish photo from storage.
        static::deleting(function (self $user): void {
            $restaurant = $user->restaurant;

            if ($restaurant !== null) {
                // This account is already on its way out; the restaurant must
                // not try to delete it a second time.
                $restaurant->ownerIsBeingDeleted = true;
                $restaurant->delete();
            }
        });
    }

    /**
     * An account made with a username; its email is optional. Stored lowercase
     * and trimmed, blank as null, so "Rami" and "rami " are one account.
     */
    protected function username(): Attribute
    {
        return Attribute::make(set: fn (?string $value): ?string => self::normalizeUsername($value));
    }

    public static function normalizeUsername(?string $value): ?string
    {
        $username = Str::lower(trim((string) $value));

        return $username === '' ? null : $username;
    }

    /**
     * Who is signing in: an email has an "@", a username never does.
     *
     * @return array{email: string}|array{username: string|null}
     */
    public static function loginCredentials(string $login): array
    {
        $login = trim($login);

        return str_contains($login, '@')
            ? ['email' => $login]
            : ['username' => self::normalizeUsername($login)];
    }

    public function restaurant(): HasOne
    {
        return $this->hasOne(Restaurant::class);
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    /** The phones this account gets notifications on (the admin app). */
    public function deviceTokens(): HasMany
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isMenuOwner(): bool
    {
        return $this->role === UserRole::MenuOwner;
    }

    public function hasCompletedOnboarding(): bool
    {
        return $this->onboarding_completed_at !== null;
    }

    /**
     * Where to send this owner right after they authenticate: their dashboard once
     * onboarding is done, otherwise the onboarding flow. Never the public landing
     * page, which offers a signed-in owner no way into the app.
     */
    public function afterLoginUrl(): string
    {
        return $this->hasCompletedOnboarding()
            ? (string) config('app.dashboard_url')
            : route('onboarding');
    }

    public function currentOnboardingStep(): int
    {
        return min(($this->onboarding_step ?? 0) + 1, self::ONBOARDING_STEPS);
    }

    public function canAccessPanel(\Filament\Panel $panel): bool
    {
        return $this->isAdmin();
    }
}
