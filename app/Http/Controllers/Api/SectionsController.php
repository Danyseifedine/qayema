<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateSectionsRequest;
use Illuminate\Http\JsonResponse;

/**
 * Which optional dashboard sections the owner has switched off. Purely a
 * dashboard preference: hiding Orders does not stop guests ordering, and
 * nothing a hidden section holds is touched.
 */
class SectionsController extends Controller
{
    public function update(UpdateSectionsRequest $request): JsonResponse
    {
        $restaurant = $request->user()->restaurant;
        $restaurant->update(['hidden_sections' => array_values($request->validated('hidden'))]);

        return response()->json(['data' => ['hidden' => $restaurant->hiddenSections()]]);
    }
}
