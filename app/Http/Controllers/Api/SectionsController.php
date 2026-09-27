<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSectionsRequest;
use Illuminate\Http\JsonResponse;

/**
 * Which optional features the owner has switched off (see
 * Restaurant::OPTIONAL_FEATURES for what each one does). Nothing a feature
 * holds is deleted; switching it back on brings it all back.
 */
class SectionsController extends Controller
{
    public function update(UpdateSectionsRequest $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $restaurant->update(['hidden_sections' => array_values($request->validated('hidden'))]);

        return response()->json(['data' => ['hidden' => $restaurant->switchedOff()]]);
    }
}
