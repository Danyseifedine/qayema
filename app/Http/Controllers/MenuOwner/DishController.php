<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DishRequest;
use App\Models\Category;
use App\Models\Dish;
use App\Models\Menu;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DishController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        $dishes = $menu
            ? Dish::where('menu_id', $menu->id)
                ->with('category')
                ->orderBy('display_order')
                ->orderBy('name')
                ->get()
            : collect();

        return view('menu-owner.dishes.index', [
            'dishes' => $dishes,
            'menu' => $menu,
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding dishes.');
        }

        if ($menu->hasReachedDishLimit()) {
            return redirect()->route('menu-owner.dishes.index')
                ->with('error', 'You have reached the maximum number of dishes allowed.');
        }

        $categories = Category::where('menu_id', $menu->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('menu-owner.dishes.form', [
            'dish' => null,
            'menu' => $menu,
            'categories' => $categories,
        ]);
    }

    public function store(DishRequest $request): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        if (! $menu) {
            return redirect()->route('menu-owner.menus.index')
                ->with('error', 'Please create a menu first before adding dishes.');
        }

        // Check dish limit
        if ($menu->hasReachedDishLimit()) {
            return redirect()->route('menu-owner.dishes.index')
                ->with('error', 'You have reached the maximum number of dishes allowed for your menu.');
        }

        $data = $request->validated();
        $data['menu_id'] = $menu->id;

        // Set removed fields to null
        $data['description'] = null;
        $data['prep_time'] = null;
        $data['serving_size'] = null;
        $data['allergens'] = null;

        $dish = Dish::create($data);

        // Handle image uploads if provided
        if ($request->hasFile('images')) {
            $imageService = app(\App\Services\ImageOptimizationService::class);
            foreach ($request->file('images') as $image) {
                try {
                    // Optimize image to max 50KB (600x600 for menu display)
                    $optimizedPath = $imageService->optimizeDishImage($image);

                    // Ensure file exists and is readable
                    if (file_exists($optimizedPath) && is_readable($optimizedPath)) {
                        // Use the same approach as RestaurantSetupController
                        $dish->addMedia($optimizedPath)
                            ->usingName(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
                            ->toMediaCollection('images');

                        // Clean up temporary file after Media Library copies it
                        if (file_exists($optimizedPath)) {
                            @unlink($optimizedPath);
                        }
                    } else {
                        \Log::error('Optimized file does not exist or is not readable: '.$optimizedPath);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to save dish image: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());
                    // Continue with other images even if one fails
                }
            }
        }

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish created successfully!');
    }

    public function edit(Request $request, Dish $dish): View|RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure dish belongs to user's menu
        if (! $menu || $dish->menu_id !== $menu->id) {
            abort(404);
        }

        $categories = Category::where('menu_id', $menu->id)
            ->orderBy('display_order')
            ->orderBy('name')
            ->get();

        return view('menu-owner.dishes.form', [
            'dish' => $dish,
            'menu' => $menu,
            'categories' => $categories,
        ]);
    }

    public function update(DishRequest $request, Dish $dish): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure dish belongs to user's menu
        if (! $menu || $dish->menu_id !== $menu->id) {
            abort(404);
        }

        \Log::info('Dish update called', [
            'dish_id' => $dish->id,
            'has_files' => $request->hasFile('images'),
            'files_count' => $request->hasFile('images') ? count($request->file('images')) : 0,
            'all_files' => $request->allFiles(),
        ]);

        $data = $request->validated();

        // Set removed fields to null
        $data['description'] = null;
        $data['prep_time'] = null;
        $data['serving_size'] = null;
        $data['allergens'] = null;

        $dish->update($data);

        // Handle image deletion first
        if ($request->has('delete_images') && is_array($request->input('delete_images'))) {
            foreach ($request->input('delete_images') as $imageId) {
                $media = $dish->getMedia('images')->where('id', (int) $imageId)->first();
                if ($media) {
                    $media->delete();
                }
            }
        }

        // Handle image uploads if provided
        if ($request->hasFile('images')) {
            \Log::info('Processing image uploads', ['count' => count($request->file('images'))]);
            $imageService = app(\App\Services\ImageOptimizationService::class);
            foreach ($request->file('images') as $index => $image) {
                try {
                    \Log::info("Processing image {$index}", [
                        'original_name' => $image->getClientOriginalName(),
                        'size' => $image->getSize(),
                        'mime' => $image->getMimeType(),
                    ]);

                    // Optimize image to max 50KB (600x600 for menu display)
                    $optimizedPath = $imageService->optimizeDishImage($image);
                    \Log::info('Image optimized', ['path' => $optimizedPath, 'exists' => file_exists($optimizedPath)]);

                    // Ensure file exists and is readable
                    if (file_exists($optimizedPath) && is_readable($optimizedPath)) {
                        \Log::info('Adding media to collection');
                        // Use the same approach as RestaurantSetupController
                        $media = $dish->addMedia($optimizedPath)
                            ->usingName(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
                            ->toMediaCollection('images');
                        \Log::info('Media added successfully', ['media_id' => $media->id ?? 'unknown']);

                        // Clean up temporary file after Media Library copies it
                        if (file_exists($optimizedPath)) {
                            @unlink($optimizedPath);
                            \Log::info('Temporary file cleaned up');
                        }
                    } else {
                        \Log::error('Optimized file does not exist or is not readable: '.$optimizedPath);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to save dish image: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());
                    // Continue with other images even if one fails
                }
            }
        } else {
            \Log::info('No images in request - hasFile check returned false');
        }

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish updated successfully!');
    }

    public function destroy(Request $request, Dish $dish): RedirectResponse
    {
        $user = $request->user();
        $menu = $user->menus()->first();

        // Ensure dish belongs to user's menu
        if (! $menu || $dish->menu_id !== $menu->id) {
            abort(404);
        }

        $dish->delete();

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish deleted successfully!');
    }
}
