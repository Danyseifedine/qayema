<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRestaurantSetupComplete
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        // Allow admins to bypass
        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        // Check if user is menu owner and setup is not complete
        if ($user && ! $user->isRestaurantSetupComplete()) {
            // Allow access to setup routes and profile
            if (! $request->routeIs('restaurant-setup.*') && ! $request->routeIs('profile.*') && ! $request->routeIs('logout')) {
                return redirect()->route('restaurant-setup.index');
            }
        }

        return $next($request);
    }
}
