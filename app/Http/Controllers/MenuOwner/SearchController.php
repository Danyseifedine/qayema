<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $q = trim($request->string('q'));

        if (strlen($q) < 1) {
            return response()->json(['dishes' => [], 'categories' => []]);
        }

        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return response()->json(['dishes' => [], 'categories' => []]);
        }

        $term = '%'.$q.'%';

        $dishes = Dish::where('restaurant_id', $restaurant->id)
            ->where('name', 'like', $term)
            ->orderBy('name')
            ->limit(6)
            ->get(['id', 'name', 'is_available']);

        $categories = Category::where('restaurant_id', $restaurant->id)
            ->where('name', 'like', $term)
            ->orderBy('name')
            ->limit(4)
            ->get(['id', 'name']);

        return response()->json([
            'dishes' => $dishes,
            'categories' => $categories,
        ]);
    }
}
