<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user && ! $user->isAdmin() && ! $user->hasCompletedOnboarding()) {
            if (
                ! $request->routeIs('onboarding') &&
                ! $request->routeIs('onboarding.advance') &&
                ! $request->routeIs('logout') &&
                ! $request->routeIs('menu-owner.temp-upload')
            ) {
                return redirect()->route('onboarding');
            }
        }

        return $next($request);
    }
}
