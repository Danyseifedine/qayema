<?php

namespace App\Http\Controllers\MenuOwner;

use App\Enums\MenuScanStatus;
use App\Http\Controllers\Controller;
use App\Jobs\ProcessMenuScan;
use App\Models\Category;
use App\Models\Dish;
use App\Models\MenuScan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MenuScanController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.menu-scan.index', [
            'restaurant' => $request->user()->restaurant,
        ]);
    }

    /**
     * Accept image, store it, create a MenuScan record, dispatch the job.
     * Returns immediately — the client polls /menu-scan/{id}/status.
     */
    public function scan(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'max:10240', 'mimes:jpeg,png,webp'],
        ]);

        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return response()->json(['message' => 'No restaurant found.'], 422);
        }

        $path = $request->file('image')->store('menu-scans', 'local');

        $scan = MenuScan::create([
            'restaurant_id' => $restaurant->id,
            'status' => MenuScanStatus::Pending,
            'image_path' => $path,
        ]);

        ProcessMenuScan::dispatch($scan->id);

        return response()->json([
            'scan_id' => $scan->id,
            'status' => $scan->status->value,
        ], 202);
    }

    /**
     * Poll endpoint — returns current status + result/error when ready.
     */
    public function status(Request $request, int $id): JsonResponse
    {
        $scan = MenuScan::find($id);

        if (! $scan) {
            return response()->json(['message' => 'Scan not found.'], 404);
        }

        if ($scan->restaurant_id !== $request->user()->restaurant?->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'status' => $scan->status->value,
            'result' => $scan->result,
            'error' => $scan->error,
        ]);
    }

    /**
     * Confirm and import — creates categories + dishes from a completed scan.
     */
    public function import(Request $request, int $id): JsonResponse
    {
        $scan = MenuScan::find($id);

        if (! $scan) {
            return response()->json(['message' => 'Scan not found.'], 404);
        }

        $restaurant = $request->user()->restaurant;

        if (! $restaurant || $scan->restaurant_id !== $restaurant->id) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        if (! $scan->isCompleted()) {
            return response()->json(['message' => 'Scan is not ready to import yet.'], 422);
        }

        if (empty($scan->result['categories'])) {
            return response()->json(['message' => 'No menu data was extracted from the image.'], 422);
        }

        $created = DB::transaction(function () use ($scan, $restaurant) {
            $allDishes = [];
            $categoryCount = 0;
            $now = now();

            foreach ($scan->result['categories'] as $order => $categoryData) {
                $category = Category::create([
                    'restaurant_id' => $restaurant->id,
                    'name' => $categoryData['name'],
                    'display_order' => $order,
                ]);

                $categoryCount++;

                foreach (($categoryData['dishes'] ?? []) as $dishOrder => $dishData) {
                    $allDishes[] = [
                        'restaurant_id' => $restaurant->id,
                        'category_id' => $category->id,
                        'name' => $dishData['name'],
                        'price' => $dishData['price'] ?? null,
                        'ingredients' => $dishData['ingredients'] ?? null,
                        'is_available' => true,
                        'display_order' => $dishOrder,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }

            if (! empty($allDishes)) {
                Dish::insert($allDishes);
            }

            $scan->delete();

            return ['categories' => $categoryCount, 'dishes' => count($allDishes)];
        });

        return response()->json([
            'message' => "Imported {$created['categories']} categories and {$created['dishes']} dishes successfully.",
            'created' => $created,
        ]);
    }
}
