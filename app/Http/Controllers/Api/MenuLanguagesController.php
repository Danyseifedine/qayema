<?php

namespace App\Http\Controllers\Api;

use App\Enums\Feature;
use App\Http\Controllers\Concerns\ResolvesRestaurant;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateMenuLanguagesRequest;
use App\Models\Restaurant;
use App\Services\Menu\MenuLanguages;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The menu's languages, set from the dashboard's Features page: the main one
 * (any language, every package), the second one and the one it opens in.
 * Changing a language never deletes text: what was written in the old one
 * stays, hidden, for if it comes back, and making the second language the
 * main one is a swap with nothing to copy. Choosing a second language needs
 * the package's `multiple_languages` flag; the main language never does.
 */
class MenuLanguagesController extends Controller
{
    use ResolvesRestaurant;

    /** The languages, and what still has no name in the main one. */
    public function show(Request $request): JsonResponse
    {
        return response()->json(['data' => $this->state($this->restaurant($request))]);
    }

    public function update(UpdateMenuLanguagesRequest $request): JsonResponse
    {
        $restaurant = $this->restaurant($request);
        abort_if(
            $request->validated('second_locale') !== null && ! $restaurant->entitlements()->can(Feature::MultipleLanguages),
            403,
            __('A second menu language is not on your package.'),
        );

        $restaurant->update([
            'main_locale' => $request->validated('main_locale', MenuLanguages::main($restaurant)),
            'second_locale' => $request->validated('second_locale'),
            'default_locale' => $request->validated('default_locale'),
        ]);

        return response()->json(['data' => $this->state($restaurant)]);
    }

    /**
     * @return array{languages: array<int, string>, main_locale: string, second_locale: string|null, default_locale: string, missing: array{categories: int, dishes: int}}
     */
    private function state(Restaurant $restaurant): array
    {
        return [
            'languages' => $restaurant->menuLanguages(),
            'main_locale' => MenuLanguages::main($restaurant),
            'second_locale' => MenuLanguages::written($restaurant)[1] ?? null,
            'default_locale' => MenuLanguages::default($restaurant),
            'missing' => MenuLanguages::missingInMain($restaurant),
        ];
    }
}
