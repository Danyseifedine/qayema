<?php

namespace App\Http\Middleware;

use App\Services\Global\AbuseGuard;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockAbusiveIps
{
    public function __construct(private AbuseGuard $abuseGuard) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->abuseGuard->isBlocked($request->ip()) && ! auth()->user()?->isAdmin()) {
            abort(403, 'Your access has been temporarily blocked.');
        }

        return $next($request);
    }
}
