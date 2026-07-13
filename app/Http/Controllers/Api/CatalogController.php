<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Global\FeatureCatalog;
use App\Services\Global\PaddlePrices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    /**
     * The purchasable add-on catalog with live Paddle amounts, so the SPA's
     * upgrade page always displays exactly what Paddle will charge. An entry
     * whose amount is null (Paddle unreachable, price not yet created) is shown
     * by the SPA as unavailable rather than with a stale hardcoded price.
     * `remaining` is what THIS restaurant may still buy under the entry's
     * lifetime cap (null = uncapped); the SPA caps its steppers with it.
     */
    public function index(Request $request, FeatureCatalog $catalog, PaddlePrices $prices): JsonResponse
    {
        $restaurant = $request->user()->restaurant;

        abort_if($restaurant === null, 403, __('Create your restaurant before browsing add-ons.'));

        $amounts = $prices->amounts();

        $data = [];

        foreach ($catalog->all() as $id => $entry) {
            $max = isset($entry['max']) ? (int) $entry['max'] : null;

            $data[] = [
                'id' => $id,
                'slug' => $entry['slug'],
                'kind' => $entry['kind'],
                'step' => (int) ($entry['step'] ?? 1),
                'max' => $max,
                'remaining' => $max === null
                    ? null
                    : max(0, $max - $restaurant->purchasedFeatureAmount($entry['slug'])),
                'current' => $entry['kind'] === 'limit'
                    ? $restaurant->package()->limit($entry['slug'])
                    : null,
                'unit_amount' => $amounts[$id]['unit_amount'] ?? null,
                'currency' => $amounts[$id]['currency'] ?? null,
            ];
        }

        return response()->json(['data' => $data]);
    }
}
