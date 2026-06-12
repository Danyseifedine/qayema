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
    private const DAILY_SCAN_LIMIT = 3;

    public function index(Request $request): View
    {
        $restaurant = $request->user()->restaurant;
        $scansToday = $restaurant
            ? MenuScan::where('restaurant_id', $restaurant->id)
                ->whereDate('created_at', today())
                ->count()
            : 0;

        return view('dashboard.menu-scan.index', [
            'restaurant' => $restaurant,
            'scansToday' => $scansToday,
            'scanLimit' => self::DAILY_SCAN_LIMIT,
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

        $scan = DB::transaction(function () use ($request, $restaurant) {
            $scansToday = MenuScan::where('restaurant_id', $restaurant->id)
                ->whereDate('created_at', today())
                ->lockForUpdate()
                ->count();

            if ($scansToday >= self::DAILY_SCAN_LIMIT) {
                return null;
            }

            $path = $request->file('image')->store('menu-scans', 'local');

            return MenuScan::create([
                'restaurant_id' => $restaurant->id,
                'status' => MenuScanStatus::Pending,
                'image_path' => $path,
            ]);
        });

        if (! $scan) {
            return response()->json(['message' => 'daily_limit', 'limit' => self::DAILY_SCAN_LIMIT], 429);
        }

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
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $scan = $restaurant->menuScans()->findOrFail($id);

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
        $restaurant = $request->user()->restaurant;

        if (! $restaurant) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $scan = $restaurant->menuScans()->findOrFail($id);

        if (! $scan->isCompleted()) {
            return response()->json(['message' => 'Scan is not ready to import yet.'], 422);
        }

        if (! empty($scan->result['imported'])) {
            return response()->json(['message' => 'This scan has already been imported.'], 422);
        }

        if (empty($scan->result['categories'])) {
            return response()->json(['message' => 'No menu data was extracted from the image.'], 422);
        }

        // Validate the (possibly edited) categories sent from the frontend.
        $request->validate([
            'categories' => ['required', 'array', 'max:'.$restaurant->category_limit],
            'categories.*.name' => ['required', 'string', 'max:255'],
            'categories.*.dishes' => ['nullable', 'array', 'max:'.$restaurant->dish_limit],
            'categories.*.dishes.*.name' => ['required', 'string', 'max:255'],
            'categories.*.dishes.*.price' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'categories.*.dishes.*.ingredients' => ['nullable', 'string', 'max:1000'],
        ]);

        $scanCategories = $request->input('categories');
        $scanDishCount = array_sum(array_map(fn ($c) => count($c['dishes'] ?? []), $scanCategories));
        $scanCategoryCount = count($scanCategories);

        $remainingDishes = $restaurant->dish_limit - $restaurant->dishes()->count();
        $remainingCategories = $restaurant->category_limit - $restaurant->categories()->count();

        if ($scanDishCount > $remainingDishes) {
            return response()->json([
                'message' => "Import would exceed your dish limit. You can add {$remainingDishes} more dish(es).",
            ], 422);
        }

        if ($scanCategoryCount > $remainingCategories) {
            return response()->json([
                'message' => "Import would exceed your category limit. You can add {$remainingCategories} more category(ies).",
            ], 422);
        }

        try {
            $created = DB::transaction(function () use ($scan, $restaurant, $scanCategories) {
                // Re-check inside the transaction to prevent concurrent double imports.
                $fresh = MenuScan::lockForUpdate()->find($scan->id);
                if (! $fresh || ! empty($fresh->result['imported'])) {
                    throw new \RuntimeException('already_imported');
                }

                $fresh->update(['result' => array_merge($fresh->result ?? [], ['imported' => true])]);

                $allDishes = [];
                $categoryCount = 0;
                $now = now();

                foreach ($scanCategories as $order => $categoryData) {
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

                return ['categories' => $categoryCount, 'dishes' => count($allDishes)];
            });
        } catch (\RuntimeException $e) {
            if ($e->getMessage() === 'already_imported') {
                return response()->json(['message' => 'This scan has already been imported.'], 422);
            }
            throw $e;
        }

        return response()->json([
            'message' => "Imported {$created['categories']} categories and {$created['dishes']} dishes successfully.",
            'created' => $created,
        ]);
    }
}
