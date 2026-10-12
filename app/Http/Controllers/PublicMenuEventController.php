<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuEventsRequest;
use App\Models\Restaurant;
use App\Services\Analytics\MenuEventRecorder;
use App\Services\Analytics\MenuVisitRecorder;
use Illuminate\Http\Response;

/**
 * A public menu's visit and what guests do on it, sent by
 * public/js/menu-track.js. The page never waits on this, so it answers with
 * an empty 204 either way.
 */
class PublicMenuEventController extends Controller
{
    public function store(MenuEventsRequest $request, Restaurant $restaurant, MenuVisitRecorder $visits, MenuEventRecorder $events): Response
    {
        abort_unless($restaurant->is_active, 404);

        $visitor = $request->visitor();

        if ($request->has('visit')) {
            $visits->record($restaurant, $request, $visitor, $request->validated('visit.locale'), $request->boolean('visit.qr'));
        }

        if ($request->validated('events', []) !== []) {
            $events->record($restaurant, $visitor, $request->validated('events'));
        }

        return response()->noContent();
    }
}
