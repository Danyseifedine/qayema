<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

/**
 * The web group's StartSession (bootstrap/app.php). It works as Laravel's
 * does, except on the routes grouped under self::ONLY_RETURNING: the pages a
 * stranger may open without a session, the public menu and what it calls,
 * and the website's pages without a form.
 * There, someone who already has a stored session (a signed-in owner, an
 * admin, a guest who used a form) carries on with it; anyone else, a new
 * guest or a crawler, gets a session for this request only, never stored
 * and never given a cookie. Sessions last a year (the dashboard rides
 * them), so storing one for every guest and bot would fill Redis, whose
 * room is shared with the sign-ins and the queue.
 */
class StartSessionForReturningVisitors extends StartSession
{
    /** The route group attribute that keeps strangers' sessions unstored. */
    public const ONLY_RETURNING = 'sessions_for_returning_visitors_only';

    protected function handleStatefulRequest(Request $request, $session, Closure $next)
    {
        if ($request->route()?->getAction(self::ONLY_RETURNING) !== true || $this->isStored($request, $session)) {
            return parent::handleStatefulRequest($request, $session, $next);
        }

        $request->setLaravelSession($this->startSession($request, $session));

        return $next($request);
    }

    /** Whether the request's session cookie names a session the store still holds. */
    private function isStored(Request $request, Session $session): bool
    {
        $id = $request->cookies->get($session->getName());

        return is_string($id) && $id === $session->getId() && $session->getHandler()->read($id) !== '';
    }
}
