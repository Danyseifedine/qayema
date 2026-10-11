<?php

namespace App\Http\Middleware;

use App\Models\Restaurant;
use App\Services\Ai\WritingHelp;
use App\Services\Ai\WritingHelpLog;
use App\Services\Ai\WritingHelpSetup;
use App\Services\Ai\WritingHelpUses;
use Closure;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * The way into the writing help, in this order:
 *
 * 1. The day's uses (WritingHelpUses): none left is a 429 at once.
 * 2. A request waits on the AI provider for seconds while it holds a PHP
 *    worker, the same workers the public menus need. So one runs at a time
 *    per restaurant (a double click or a second tab waits its turn), and at
 *    most config('services.writing_help.at_once') across the server. Past
 *    either, an instant "busy, try again" keeps workers free for guests.
 * 3. Then the use is taken, and given back when the answer is not a
 *    success (the provider failed, the request was refused): an owner only
 *    spends a use on text they got. A busy answer never took one.
 * 4. The request goes in the usage log (WritingHelpLog) with the tokens its
 *    calls spent, a failure's included.
 */
class PaceWritingHelp
{
    public function __construct(
        private readonly WritingHelpUses $uses,
        private readonly WritingHelpLog $log,
        private readonly WritingHelp $help,
    ) {}

    /**
     * The longest a request may keep its place, should it never let go:
     * WritingHelp's time budget with room to spare.
     */
    private const HOLD_SECONDS = 30;

    public function handle(Request $request, Closure $next): Response
    {
        $restaurant = $request->user()?->restaurant;

        // No restaurant is a 403 further on and no writing help a 404, with
        // nothing to count or wait for.
        if ($restaurant === null || ! WritingHelpSetup::isOnFor($restaurant)) {
            return $next($request);
        }

        if ($this->uses->left($restaurant) === 0) {
            return $this->spent($restaurant);
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
                $this->uses->take($restaurant);
                $response = $next($request);

                if (! $response->isSuccessful()) {
                    $this->uses->giveBack($restaurant);
                }

                $this->log->record($restaurant, $response->isSuccessful(), ...$this->help->takeTokens());

                return $response;
            } finally {
                $place->release();
            }
        } finally {
            $turn->release();
        }
    }

    /**
     * One of the server's places, or null when every one is taken. Tried
     * from a random one on, so requests side by side do not all queue for
     * the first: a free place is most often found at the first try.
     */
    private function freePlace(): ?Lock
    {
        $places = max(1, (int) config('services.writing_help.at_once'));
        $first = random_int(0, $places - 1);

        for ($try = 0; $try < $places; $try++) {
            $place = ($first + $try) % $places;
            $lock = Cache::lock("writing-help:place:{$place}", self::HOLD_SECONDS);

            if ($lock->get()) {
                return $lock;
            }
        }

        return null;
    }

    /** Every use today is spent; `left` is 0 so the dashboard can say so. */
    private function spent(Restaurant $restaurant): Response
    {
        $wait = $this->uses->backIn($restaurant);

        return response()->json([
            'message' => __('You have used today\'s writing help. It comes back tomorrow.'),
            'code' => 'writing_help_limit',
            'retry_after' => $wait,
            'left' => 0,
        ], 429, ['Retry-After' => $wait]);
    }

    private function busy(): Response
    {
        return response()->json([
            'message' => __('Writing help is busy right now. Try again in a moment.'),
            'code' => 'writing_help_busy',
        ], 503);
    }
}
