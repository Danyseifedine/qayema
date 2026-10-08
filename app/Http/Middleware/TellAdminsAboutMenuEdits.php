<?php

namespace App\Http\Middleware;

use App\Services\Push\AdminAlerts;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * An owner changing their menu from the dashboard (a dish, a category, the
 * design, the restaurant's details or link, the QR code, the languages, the
 * social links) tells the admins' phones, once an hour at most
 * (AdminAlerts::menuEditing). Orders, the account and the features page are
 * not menu edits; an admin signed in as the owner from /admin is not one
 * either; a change that failed is not one.
 */
class TellAdminsAboutMenuEdits
{
    /** The owner API's routes that change what guests see on the menu. */
    public const MENU_ROUTES = [
        'api.categories.*',
        'api.dishes.*',
        'api.restaurant.*',
        'api.appearance.*',
        'api.templates.*',
        'api.qr.*',
        'api.menu-languages.*',
        'api.social-links.*',
    ];

    public function __construct(private readonly AdminAlerts $alerts) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($this->isMenuEdit($request, $response)) {
            $restaurant = $request->user()->restaurant;
            rescue(fn () => $restaurant === null ? null : $this->alerts->menuEditing($restaurant));
        }

        return $response;
    }

    private function isMenuEdit(Request $request, Response $response): bool
    {
        return ! $request->isMethodSafe()
            && $response->isSuccessful()
            && $request->routeIs(self::MENU_ROUTES)
            && $request->user()?->isMenuOwner() === true
            && ! app('impersonate')->isImpersonating();
    }
}
