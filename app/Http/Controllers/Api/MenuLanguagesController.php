<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMenuLanguagesRequest;
use App\Services\Menu\MenuLanguages;
use Illuminate\Http\JsonResponse;

/**
 * The menu's second language and the one it opens in, set from the
 * dashboard's Features page. Changing the second language never deletes text:
 * what was written in the old one stays, hidden, for if it comes back.
 * Choosing a second language needs the package's `multiple_languages` flag;
 * going back to English only never does.
 */
class MenuLanguagesController extends Controller
{
    use ResolvesRestaurant;

    public function update(UpdateMenuLanguagesRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_if(
            $request->validated('second_locale') !== null && ! $restaurant->entitlements()->can(Feature::MultipleLanguages),
            403,
            __('A second menu language is not on your package.'),
        );

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
