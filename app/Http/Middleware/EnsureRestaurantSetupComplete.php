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

        if ($user && $user->isAdmin()) {
            return $next($request);
        }

        if ($user && ! $user->restaurant) {
            if (! $request->routeIs('menu-owner.restaurant.*') && ! $request->routeIs('profile.*') && ! $request->routeIs('logout')) {
                return redirect()->route('menu-owner.restaurant.index');
            }
        }

        return $next($request);
    }
}
