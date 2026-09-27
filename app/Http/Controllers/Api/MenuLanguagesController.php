<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMenuLanguagesRequest;
use App\Services\Global\MenuLanguages;
use Illuminate\Http\JsonResponse;

/**
 * The menu's second language and the one it opens in, set from the
 * dashboard's Features page. Changing the second language never deletes text:
 * what was written in the old one stays, hidden, for if it comes back.
 */
class MenuLanguagesController extends Controller
{
    public function update(UpdateMenuLanguagesRequest $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        $restaurant->update([
            'second_locale' => $request->validated('second_locale'),
            'default_locale' => $request->validated('default_locale'),
        ]);

        return response()->json(['data' => [
            'languages' => $restaurant->menuLanguages(),
            'second_locale' => MenuLanguages::written($restaurant)[1] ?? null,
            'default_locale' => MenuLanguages::default($restaurant),
        ]]);
    }
}
