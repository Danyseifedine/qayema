<?php

namespace App\Http\Controllers\MenuOwner;

use App\Http\Controllers\Controller;
use App\Http\Requests\DishRequest;
use App\Models\Category;
use App\Models\Dish;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DishController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $menu = $user->currentMenu();

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
        $menu = $user->currentMenu();

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
        $menu = $user->currentMenu();

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
        $data['allergens'] = $this->parseAllergens($request->input('allergens'));

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
        $this->authorize('update', $dish);

        $menu = $dish->menu;
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
        $this->authorize('update', $dish);

        $data = $request->validated();
        $data['allergens'] = $this->parseAllergens($request->input('allergens'));

        $dish->update($data);

        if ($request->has('delete_images') && is_array($request->input('delete_images'))) {
            foreach ($request->input('delete_images') as $imageId) {
                $media = $dish->getMedia('images')->where('id', (int) $imageId)->first();
                if ($media) {
                    $media->delete();
                }
            }
        }

        if ($request->hasFile('images')) {
            $imageService = app(\App\Services\ImageOptimizationService::class);
            foreach ($request->file('images') as $image) {
                try {
                    $optimizedPath = $imageService->optimizeDishImage($image);

                    if (file_exists($optimizedPath) && is_readable($optimizedPath)) {
                        $dish->addMedia($optimizedPath)
                            ->usingName(pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME))
                            ->toMediaCollection('images');

                        if (file_exists($optimizedPath)) {
                            @unlink($optimizedPath);
                        }
                    } else {
                        \Log::error('Optimized file does not exist or is not readable: '.$optimizedPath);
                    }
                } catch (\Exception $e) {
                    \Log::error('Failed to save dish image: '.$e->getMessage().' | Trace: '.$e->getTraceAsString());
                }
            }
        }

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish updated successfully!');
    }

    public function destroy(Request $request, Dish $dish): RedirectResponse
    {
        $this->authorize('delete', $dish);

        $dish->delete();

        return redirect()->route('menu-owner.dishes.index')
            ->with('success', 'Dish deleted successfully!');
    }

    /**
     * @return array<int, string>|null
     */
    protected function parseAllergens(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value)));
        }

        return array_values(array_filter(array_map('trim', explode(',', (string) $value))));
    }
}
