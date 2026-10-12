<?php

namespace App\Http\Middleware;

use App\Services\Menu\MenuToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The CSRF check of the public menu's own requests (routes/web.php): they
 * must carry the token the menu page was given (MenuToken). One missing,
 * forged or from a page open too long is a 419, as an expired CSRF token
 * is, and the cart shows its message.
 */
class VerifyMenuToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! MenuToken::isValid($request->header(MenuToken::HEADER))) {
            return response()->json([
                'message' => __('This page was open for too long. Refresh it and try again.'),
                'code' => 'menu_expired',
            ], 419);
        }

        return $next($request);
    }
}
