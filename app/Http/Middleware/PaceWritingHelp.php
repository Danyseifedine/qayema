<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * A writing help request waits on the AI provider for seconds while it
 * holds a PHP worker, the same workers the public menus need. So one runs
 * at a time per restaurant (a double click or a second tab waits its turn),
 * and at most config('services.writing_help.at_once') across the server.
 * Past either, the answer is an instant "busy, try again", which keeps
 * workers free for guests. Runs before the daily allowance is counted, so a
 * busy answer costs the owner nothing.
 */
class PaceWritingHelp
{
    /**
     * The longest a request may keep its place, should it never let go:
     * WritingHelp's time budget with room to spare.
     */
    private const HOLD_SECONDS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->user()?->restaurant;

        // No restaurant is a 403 further on, with nothing to wait for.
        if ($restaurant === null) {
            return $next($request);
        }

        $turn = Cache::lock("writing-help:restaurant:{$restaurant->id}", self::HOLD_SECONDS);

        if (! $turn->get()) {
            return $this->busy();
        }

        try {
            $place = $this->freePlace();

            if ($place === null) {
                return $this->busy();
            }

            try {
                return $next($request);
            } finally {
                $place->release();
            }
        } finally {
            $turn->release();
        }
    }

    /** One of the server's places, or null when every one is taken. */
    private function freePlace(): ?Lock
    {
        $places = max(1, (int) config('services.writing_help.at_once'));

        for ($place = 0; $place < $places; $place++) {
            $lock = Cache::lock("writing-help:place:{$place}", self::HOLD_SECONDS);

            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }

    private function busy(): Response
    {
        return response()->json([
            'message' => __('Writing help is busy right now. Try again in a moment.'),
            'code' => 'writing_help_busy',
        ], 503);
    }
}
