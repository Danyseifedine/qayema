<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuEventsRequest;
use App\Models\Restaurant;
use App\Services\Global\MenuEventRecorder;
use Illuminate\Http\Response;

/**
 * What guests do on a public menu, sent by public/js/menu-track.js. The page
 * never waits on this, so it answers with an empty 204 either way.
 */
class PublicMenuEventController extends Controller
{
    public function store(MenuEventsRequest $request, Restaurant $restaurant, MenuEventRecorder $recorder): Response
    {
        abort_unless($restaurant->is_active, 404);

        $recorder->record($restaurant, $request, $request->validated('events'));

        return response()->noContent();
    }
}
