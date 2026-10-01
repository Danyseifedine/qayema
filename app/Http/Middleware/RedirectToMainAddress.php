<?php

namespace App\Http\Middleware;

use App\Support\SiteAddress;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * In production a visit to www.qayema.com/anything moves for good (301) to
 * qayema.com/anything, so Google sees one copy of each page rather than two
 * and picks no canonical of its own. Only reads (GET, HEAD) are moved: a
 * form posted to the www. address still goes through.
 */
class RedirectToMainAddress
{
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->isProduction()
            && $request->isMethodSafe()
            && strcasecmp($request->getHost(), 'www.'.SiteAddress::host()) === 0) {
            return redirect()->away(SiteAddress::root().$request->getRequestUri(), 301);
        }

        return $next($request);
    }
}
