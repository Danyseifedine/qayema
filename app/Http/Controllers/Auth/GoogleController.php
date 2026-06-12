<?php

namespace App\Http\Controllers\Auth;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('login')
                ->with('error', 'Google sign-in failed. Please try again.');
        }

        $socialAccount = SocialAccount::where('provider', 'google')
            ->where('provider_user_id', $googleUser->getId())
            ->first();

        if ($socialAccount) {
            $socialAccount->update([
                'access_token' => $googleUser->token,
                'refresh_token' => $googleUser->refreshToken,
                'token_expires_at' => $googleUser->expiresIn
                    ? now()->addSeconds($googleUser->expiresIn)
                    : null,
            ]);

            Auth::login($socialAccount->user, remember: true);

            return redirect()->intended(route('dashboard'));
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if ($user->email_verified_at === null) {
                $user->forceFill(['email_verified_at' => now()])->save();
            }

            $this->attachSocialAccount($user, $googleUser);
            Auth::login($user, remember: true);

            return redirect()->route('dashboard');
        }

        $user = User::create([
            'name' => $googleUser->getName(),
            'email' => $googleUser->getEmail(),
            'password' => null,
            'role' => UserRole::MenuOwner,
            'onboarding_step' => 0,
        ]);

        $user->forceFill(['email_verified_at' => now()])->save();

        $this->attachSocialAccount($user, $googleUser);
        Auth::login($user, remember: true);

        return redirect()->route('onboarding');
    }

    private function attachSocialAccount(User $user, \Laravel\Socialite\Contracts\User $googleUser): void
    {
        $user->socialAccounts()->create([
            'provider' => 'google',
            'provider_user_id' => $googleUser->getId(),
            'access_token' => $googleUser->token,
            'refresh_token' => $googleUser->refreshToken,
            'token_expires_at' => $googleUser->expiresIn
                ? now()->addSeconds($googleUser->expiresIn)
                : null,
            'avatar' => $googleUser->getAvatar(),
        ]);
    }
}
